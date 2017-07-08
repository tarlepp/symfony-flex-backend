<?php
declare(strict_types=1);
/**
 * /src/Rest/Describer/ApiDocDescriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Annotation\RestApiDoc;
use App\Rest\Doc\RouteModel;
use Doctrine\Common\Annotations\AnnotationReader;
use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Parameter;
use EXSyst\Component\Swagger\Response;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
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
    /**
     * @var RouteCollection
     */
    private $routeCollection;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @param RouteCollection $routeCollection
     */
    public function __construct(RouteCollection $routeCollection)
    {
        $this->routeCollection = $routeCollection;
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param Swagger $api
     *
     * @throws \ReflectionException
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
            $routeModel->setReflection($reflection);
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
     * Method to process method '@Security' annotation. If this annotation is present we need to following things:
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

            $responseData = [
                'description'   => 'Invalid token',
                'examples'      => [
                    'Token not found'   => '{code: 401, message: JWT Token not found}',
                    'Expired token'     => '{code: 401, message: Expired JWT Token}',
                ],
            ];

            $response = new Response($responseData);

            $operation->getResponses()->set(401, $response);
        }
    }
}
