<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/ApiDocDescriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Annotation\RestApiDoc;
use App\Rest\Doc\RouteModel;
use Doctrine\Common\Annotations\AnnotationReader;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
    /**
     * @var RouteCollection
     */
    private $routeCollection;

    /**
     * @var Rest
     */
    private $rest;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var Swagger $api
     */
    private $api;

    /**
     * @param RouterInterface $router
     * @param Rest            $rest
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct(RouterInterface $router, Rest $rest)
    {
        $this->routeCollection = $router->getRouteCollection();
        $this->rest = $rest;
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param Swagger $api
     *
     * @throws \ReflectionException
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function describe(Swagger $api): void
    {
        $this->api = $api;

        foreach ($this->getRouteModels() as $routeModel) {
            $path = $api->getPaths()->get($routeModel->getRoute()->getPath());

            if ($path->hasOperation($routeModel->getHttpMethod())) {
                $this->rest->createDocs($path->getOperation($routeModel->getHttpMethod()), $routeModel);
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
            [$controller] = \explode('::', $route->getDefault('_controller'));

            $reflection = new \ReflectionClass($controller);

            $annotations = $this->annotationReader->getClassAnnotations($reflection);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof RestApiDoc && $annotation->disabled) {
                    $output = false;

                    $this->api->getPaths()->remove($route->getPath());
                }
            }
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
}
