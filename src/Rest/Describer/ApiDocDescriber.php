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
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @param RouteCollection    $routeCollection
     * @param ContainerInterface $container
     */
    public function __construct(RouteCollection $routeCollection, ContainerInterface $container)
    {
        $this->routeCollection = $routeCollection;
        $this->container = $container;
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
}
