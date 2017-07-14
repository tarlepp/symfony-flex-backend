<?php
declare(strict_types=1);
/**
 * /src/Rest/Describer/ApiDocDescriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Annotation\RestApiDoc;
use App\Rest\Controller;
use App\Rest\Doc\RouteModel;
use Doctrine\Common\Annotations\AnnotationReader;
use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Parameter;
use EXSyst\Component\Swagger\Response;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class ApiDocDescriber
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiDocDescriber implements DescriberInterface
{
    const COUNT_ACTION      = 'countAction';
    const CREATE_ACTION     = 'createAction';
    const DELETE_ACTION     = 'deleteAction';
    const FIND_ACTION       = 'findAction';
    const FIND_ONE_ACTION   = 'findOneAction';
    const IDS_ACTION        = 'idsAction';
    const PATCH_ACTION      = 'patchAction';
    const UPDATE_ACTION     = 'updateAction';

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Twig_Environment
     */
    private $templateEngine;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @param RouterInterface    $router
     * @param ContainerInterface $container
     * @param \Twig_Environment  $templateEngine
     */
    public function __construct(
        RouterInterface $router,
        ContainerInterface $container,
        \Twig_Environment $templateEngine
    )
    {
        $this->routeCollection = $router->getRouteCollection();
        $this->container = $container;
        $this->templateEngine = $templateEngine;
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param Swagger $api
     *
     * @throws \ReflectionException
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function describe(Swagger $api): void
    {
        foreach ($this->getRouteModels() as $routeModel) {
            $path = $api->getPaths()->get($routeModel->getRoute()->getPath());

            if ($path->hasOperation($routeModel->getHttpMethod())) {
                $this->createDocs($path->getOperation($routeModel->getHttpMethod()), $routeModel);
            }
        }
    }

    /**
     * @return RouteModel[]
     *
     * @throws \ReflectionException
     */
    private function getRouteModels(): array
    {
        /**
         * Simple filter lambda function to filter out all but Method class
         *
         * @param $annotation
         *
         * @return bool
         */
        $annotationFilterMethod = function ($annotation): bool {
            return $annotation instanceof Method;
        };

        /**
         * Simple filter lambda function to filter out all but Method class
         *
         * @param $annotation
         *
         * @return bool
         */
        $annotationFilterRoute = function ($annotation): bool {
            return $annotation instanceof \Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
        };

        $iterator = function (Route $route) use ($annotationFilterMethod, $annotationFilterRoute): RouteModel {
            [$controller, $method] = \explode('::', $route->getDefault('_controller'));

            $reflection = new \ReflectionMethod($controller, $method);
            $methodAnnotations = $this->annotationReader->getMethodAnnotations($reflection);
            $controllerAnnotations = $this->annotationReader->getClassAnnotations($reflection->getDeclaringClass());

            /** @var Method $httpMethodAnnotation */
            $httpMethodAnnotation = \array_values(\array_filter($methodAnnotations, $annotationFilterMethod))[0];

            /** @var \Sensio\Bundle\FrameworkExtraBundle\Configuration\Route $routeAnnotation */
            $routeAnnotation = \array_values(\array_filter($controllerAnnotations, $annotationFilterRoute))[0];

            $routeModel = new RouteModel();
            $routeModel->setController($controller);
            $routeModel->setMethod($method);
            $routeModel->setHttpMethod(\mb_strtolower($httpMethodAnnotation->getMethods()[0]));
            $routeModel->setBaseRoute($routeAnnotation->getPath());
            $routeModel->setRoute($route);
            $routeModel->setMethodAnnotations($methodAnnotations);
            $routeModel->setControllerAnnotations($controllerAnnotations);

            return $routeModel;
        };

        return \array_map($iterator, \array_filter($this->routeCollection->all(), [$this, 'routeFilter']));
    }

    /**
     * @param Route $route
     *
     * @return bool
     *
     * @throws \ReflectionException
     */
    private function routeFilter(Route $route): bool
    {
        $output = false;

        if (!$route->hasDefault('_controller') || \mb_strrpos($route->getDefault('_controller'), '::')) {
            $output = true;
        }

        if ($output) {
            [$controller, $method] = \explode('::', $route->getDefault('_controller'));

            $reflection = new \ReflectionMethod($controller, $method);

            $annotations = $this->annotationReader->getMethodAnnotations($reflection);

            $supported = [];

            foreach ($annotations as $annotation) {
                if ($annotation instanceof RestApiDoc || $annotation instanceof Method) {
                    $supported[] = true;
                }
            }

            $output = \count($supported) === 2;
        }

        return $output;
    }

    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function createDocs(Operation $operation, RouteModel $routeModel): void
    {
        // Initialize main data array
        $data = [
            'tags' => [],
        ];

        // Process custom REST API documentation
        $this->processTags($routeModel, $data);
        $this->processSecurity($routeModel, $operation);
        $this->processSummary($routeModel, $operation);
        $this->processResponse($routeModel, $operation);
        $this->processParameters($routeModel, $operation);

        // And finally merge all data to current operation
        $operation->merge($data);
    }

    /**
     * Method to process operation 'tags'
     *
     * @param RouteModel $routeModel
     * @param array      $data
     */
    private function processTags(RouteModel $routeModel, array &$data): void
    {
        $filter = function ($annotation): bool {
            return $annotation instanceof SWG\Tag;
        };

        // If controller has 'SWG\Tag' annotation we will use that as a tag
        if (\count($annotations = \array_values(\array_filter($routeModel->getControllerAnnotations(), $filter))) === 1) {
            /** @var SWG\Tag $annotation */
            $annotation = $annotations[0];

            $tagName = $annotation->name;
        } else { // Otherwise just fallback to controller base route
            $tagName = $routeModel->getBaseRoute();
        }

        $data['tags'][] = $tagName;
    }

    /**
     * Method to process rest action '@Security' annotation. If this annotation is present we need to following things:
     *  1) Add 'Authorization' header parameter
     *  2) Add 401 response
     *
     * @param RouteModel $routeModel
     * @param Operation  $operation
     */
    private function processSecurity(RouteModel $routeModel, Operation $operation): void
    {
        $filter = function ($annotation): bool {
            return $annotation instanceof Security;
        };

        if (\count($annotations = \array_values(\array_filter($routeModel->getMethodAnnotations(), $filter))) === 1) {
            $parameter = [
                'type'          => 'string',
                'name'          => 'Authorization',
                'in'            => 'header',
                'required'      => true,
                'description'   => 'Authorization header',
                'default'       => 'Bearer _your_jwt_here_',
            ];
            
            $operation->getParameters()->add(new Parameter($parameter));

            $this->add401Response($operation);
            $this->add403Response($operation);
        }
    }

    /**
     * Method to process operation 'summary' information.
     *
     * @param RouteModel $routeModel
     * @param Operation  $operation
     *
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function processSummary(RouteModel $routeModel, Operation $operation): void
    {
        $summary = '';

        switch ($routeModel->getMethod()) {
            case self::COUNT_ACTION:
                $summary = 'Endpoint action to get count of entities (%s) on this resource.';
                break;
            case self::CREATE_ACTION:
                $summary = 'Endpoint action to create new entity (%s) to this resource.';
                break;
            case self::DELETE_ACTION:
                $summary = 'Endpoint action to delete specified entity (%s) from this resource.';
                break;
            case self::FIND_ACTION:
                $summary = 'Endpoint action to fetch entities (%s) from this resource.';
                break;
            case self::FIND_ONE_ACTION:
                $summary = 'Endpoint action to fetch specified entity (%s) from this resource.';
                break;
            case self::IDS_ACTION:
                $summary = 'Endpoint action to fetch entities (%s) id values from this resource.';
                break;
            case self::PATCH_ACTION:
                $summary = 'Endpoint action to create patch specified entity (%s) on this resource.';
                break;
            case self::UPDATE_ACTION:
                $summary = 'Endpoint action to create update specified entity (%s) on this resource.';
                break;
        }

        if (!empty($summary) && $this->container->has($routeModel->getController())) {
            /** @var Controller $controller */
            $controller = $this->container->get($routeModel->getController());

            $operation->setSummary(\sprintf($summary, $controller->getResource()->getEntityName()));
        }
    }

    /**
     * @param RouteModel $routeModel
     * @param Operation  $operation
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function processResponse(RouteModel $routeModel, Operation $operation): void
    {
        $description = '';
        $statusCode = 200;
        $responses = [];

        switch ($routeModel->getMethod()) {
            case self::COUNT_ACTION:
                $description = 'Count of (%s) entities';
                break;
            case self::CREATE_ACTION:
                $description = 'Created new entity (%s)';
                $statusCode = 201;
                break;
            case self::DELETE_ACTION:
                $description = 'Deleted entity (%s)';
                $responses[] = 'add404Response';
                break;
            case self::FIND_ACTION:
                $description = 'Array of fetched entities (%s)';
                break;
            case self::FIND_ONE_ACTION:
                $description = 'Fetched entity (%s)';
                $responses[] = 'add404Response';
                break;
            case self::IDS_ACTION:
                $description = 'Fetched entities (%s) primary key values';
                break;
            case self::PATCH_ACTION:
                $description = 'Deleted entity (%s)';
                $responses[] = 'add404Response';
                break;
            case self::UPDATE_ACTION:
                $description = 'Updated entity (%s)';
                $responses[] = 'add404Response';
                break;
        }

        if (!empty($description) && $this->container->has($routeModel->getController())) {
            /** @var Controller $controller */
            $controller = $this->container->get($routeModel->getController());

            $this->addOkResponse($operation, $description, $statusCode, $controller->getResource()->getEntityName());

            foreach ($responses as $method) {
                $this->$method($operation, $routeModel);
            }
        }
    }

    /**
     * @param RouteModel $routeModel
     * @param Operation  $operation
     */
    private function processParameters(RouteModel $routeModel, Operation $operation): void
    {
        $parameters = [];

        switch ($routeModel->getMethod()) {
            case self::COUNT_ACTION:
            case self::IDS_ACTION:
                $parameters[] = 'addParameterSearch';
                $parameters[] = 'addParameterCriteria';
                break;
            case self::CREATE_ACTION:
                break;
            case self::DELETE_ACTION:
            case self::PATCH_ACTION:
            case self::UPDATE_ACTION:
                $parameters[] = 'changePathParameter';
                break;
            case self::FIND_ONE_ACTION:
                $parameters[] = 'addParameterPopulate';
                $parameters[] = 'changePathParameter';
                break;
            case self::FIND_ACTION:
                $parameters[] = 'addParameterOrderBy';
                $parameters[] = 'addParameterLimit';
                $parameters[] = 'addParameterOffset';
                $parameters[] = 'addParameterSearch';
                $parameters[] = 'addParameterCriteria';
                $parameters[] = 'addParameterPopulate';
                break;
        }

        if (\count($parameters) > 0 && $this->container->has($routeModel->getController())) {
            foreach ($parameters as $method) {
                $this->$method($operation, $routeModel);
            }
        }
    }

    /**
     * @param Operation $operation
     */
    private function add401Response(Operation $operation): void
    {
        $data = [
            'description' => 'Invalid token',
            'examples' => [
                'Token not found' => '{code: 401, message: JWT Token not found}',
                'Expired token' => '{code: 401, message: Expired JWT Token}',
            ],
        ];

        $response = new Response($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set(401, $response);
    }

    /**
     * @param Operation $operation
     */
    private function add403Response(Operation $operation): void
    {
        $data = [
            'description' => 'Access denied',
            'examples' => [
                'Access denied' => '{message: Access denied., code: 0, status: 403}',
            ],
        ];

        $response = new Response($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set(403, $response);
    }

    /** @noinspection PhpUnusedParameterInspection */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     */
    private function add404Response(Operation $operation, RouteModel $routeModel): void
    {
        $data = [
            'description' => 'Not found',
            'examples' => [
                'Not found' => '{message: Not found, code: 0, status: 404}',
            ],
        ];

        $response = new Response($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set(404, $response);
    }

    /**
     * @param Operation $operation
     * @param string    $description
     * @param int       $statusCode
     * @param string    $entityName
     */
    private function addOkResponse(Operation $operation, string $description, int $statusCode, string $entityName): void
    {
        $data = [
            'description' => \sprintf($description, $entityName),
        ];

        $response = new Response($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set($statusCode, $response);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \UnexpectedValueException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function addParameterSearch(Operation $operation, RouteModel $routeModel): void
    {
        /** @var Controller $controller */
        $controller = $this->container->get($routeModel->getController());

        // Fetch used search columns for current resource
        $searchColumns = $controller->getResource()->getRepository()->getSearchColumns();

        if (\count($searchColumns) === 0) {
            return;
        }

        // Specify used  examples for this parameter
        static $examples = [
            '?search=term',
            '?search=term1+term2',
            '?search={"and": ["term1", "term2"]}',
            '?search={"or": ["term1", "term2"]}',
            '?search={"and": ["term1", "term2"], "or": ["term3", "term4"]}',
        ];

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_search.twig',
            [
                'properties' => $searchColumns,
                'examples'   => $examples,
            ]
        );

        $parameter = [
            'type'          => 'string',
            'name'          => 'search',
            'in'            => 'query',
            'required'      => false,
            'description'   => $description,
            'default'       => 'term',
        ];

        $operation->getParameters()->add(new Parameter($parameter));
    }

    /** @noinspection PhpUnusedParameterInspection */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterCriteria(Operation $operation, RouteModel $routeModel): void
    {
        // Specify used  examples for this parameter
        static $examples = [
            '?where={"property": "value"}                => WHERE entity.property = \'value\'',
            '?where={"id": [1,2,3]}                      => WHERE entity.id IN (1,2,3)',
            '?where={"prop1": "val1", "prop2": "val2"}   => WHERE entity.prop1 = \'val1\' AND entity.prop2 = \'val2\'',
            '?where={"property": "value", "id": [1,2,3]} => WHERE entity.property = \'value\' AND entity.id IN (1,2,3)',
        ];

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_criteria.twig',
            [
                'examples' => $examples,
            ]
        );

        $parameter = [
            'type'          => 'string',
            'name'          => 'where',
            'in'            => 'query',
            'required'      => false,
            'description'   => $description,
            'default'       => '{"property": "value"}',
        ];

        $operation->getParameters()->add(new Parameter($parameter));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterOrderBy(Operation $operation, RouteModel $routeModel): void
    {
        // Specify used examples for this parameter
        static $examples = [
            '?order=column1     => ORDER BY entity.column1 ASC',
            '?order=-column1    => ORDER BY entity.column2 DESC',
        ];

        // Specify used advanced examples for this parameter
        static $advancedExamples = [
            '?order[column1]=ASC                        => ORDER BY entity.column1 ASC',
            '?order[column1]=DESC                       => ORDER BY entity.column1 DESC',
            '?order[column1]=foobar                     => ORDER BY entity.column1 ASC',
            '?order[column1]=DESC&order[column2]=DESC   => ORDER BY entity.column1 DESC, entity.column2 DESC',
        ];

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_order.twig',
            [
                'examples'          => $examples,
                'advancedExamples'  => $advancedExamples,
            ]
        );

        $parameter = [
            'type'          => 'string',
            'name'          => 'order',
            'in'            => 'query',
            'required'      => false,
            'description'   => $description,
            'default'       => 'column',
        ];

        $operation->getParameters()->add(new Parameter($parameter));
    }

    /** @noinspection PhpUnusedParameterInspection */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterLimit(Operation $operation, RouteModel $routeModel): void
    {
        // Specify used  examples for this parameter
        static $examples = [
            '?limit=10',
        ];

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_limit.twig',
            [
                'examples' => $examples,
            ]
        );

        $parameter = [
            'type'          => 'integer',
            'name'          => 'limit',
            'in'            => 'query',
            'required'      => false,
            'description'   => $description,
            'default'       => 10,
        ];

        $operation->getParameters()->add(new Parameter($parameter));
    }

    /** @noinspection PhpUnusedParameterInspection */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterOffset(Operation $operation, RouteModel $routeModel): void
    {
        // Specify used  examples for this parameter
        static $examples = [
            '?offset=10',
        ];

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_offset.twig',
            [
                'examples' => $examples,
            ]
        );

        $parameter = [
            'type'          => 'integer',
            'name'          => 'offset',
            'in'            => 'query',
            'required'      => false,
            'description'   => $description,
            'default'       => 10,
        ];

        $operation->getParameters()->add(new Parameter($parameter));
    }

    /** @noinspection PhpUnusedParameterInspection */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \UnexpectedValueException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Syntax
     * @throws \Twig_Error_Runtime
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function addParameterPopulate(Operation $operation, RouteModel $routeModel): void
    {
        /** @var Controller $controller */
        $controller = $this->container->get($routeModel->getController());

        // Get resource associations
        $associations = $controller->getResource()->getAssociations();

        // Determine base name for resource serializer group
        $bits = \explode('\\', $controller->getResource()->getEntityName());
        $basename = \end($bits);

        // Specify used  examples for this parameter
        $examples = [];

        foreach ($associations as $association) {
            $examples[] = '?populate[]=' . $basename . '.' . $association;
        }

        $examples[] = '?populate[]=' . $basename . '.property';
        $examples[] = '?populate[]=' . $basename . '.prop1&populate[]=' . $basename . '.prop2';

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_populate.twig',
            [
                'examples'      => $examples,
                'associations'  => $associations,
            ]
        );

        $parameter = [
            'type'              => 'array',
            'name'              => 'populate[]',
            'collectionFormat'  => 'multi',
            'in'                => 'query',
            'required'          => false,
            'description'       => $description,
            'items'             => [
                'type'          => 'string',
            ],
        ];

        $operation->getParameters()->add(new Parameter($parameter));
    }

    /** @noinspection PhpUnusedParameterInspection */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     */
    private function changePathParameter(Operation $operation, RouteModel $routeModel): void
    {
        /** @var Parameter $parameter */
        foreach ($operation->getParameters() as $parameter) {
            if ($parameter->getIn() !== 'path') {
                continue;
            }

            $parameter->setDescription('Identifier');
            $parameter->setDefault('Identifier');
        }
    }
}
