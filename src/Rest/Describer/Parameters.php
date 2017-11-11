<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Parameters.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Rest\Controller;
use App\Rest\Doc\RouteModel;
use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Parameter;
use Psr\Container\ContainerInterface;

/**
 * Class Parameters
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Parameters
{
    // Specify used examples for this parameter
    static private $orderByExamples = [
        '?order=column1     => ORDER BY entity.column1 ASC',
        '?order=-column1    => ORDER BY entity.column1 DESC',
    ];

    // Specify used advanced examples for this parameter
    static private $orderByAdvancedExamples = [
        '?order[column1]=ASC                        => ORDER BY entity.column1 ASC',
        '?order[column1]=DESC                       => ORDER BY entity.column1 DESC',
        '?order[column1]=foobar                     => ORDER BY entity.column1 ASC',
        '?order[column1]=DESC&order[column2]=DESC   => ORDER BY entity.column1 DESC, entity.column2 DESC',
    ];

    // Specify used  examples for this parameter
    static private $criteriaExamples = [
        '?where={"property": "value"}                => WHERE entity.property = \'value\'',
        '?where={"id": [1,2,3]}                      => WHERE entity.id IN (1,2,3)',
        '?where={"prop1": "val1", "prop2": "val2"}   => WHERE entity.prop1 = \'val1\' AND entity.prop2 = \'val2\'',
        '?where={"property": "value", "id": [1,2,3]} => WHERE entity.property = \'value\' AND entity.id IN (1,2,3)',
    ];

    // Specify used  examples for this parameter
    static private $searchExamples = [
        '?search=term',
        '?search=term1+term2',
        '?search={"and": ["term1", "term2"]}',
        '?search={"or": ["term1", "term2"]}',
        '?search={"and": ["term1", "term2"], "or": ["term3", "term4"]}',
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Twig_Environment
     */
    private $templateEngine;

    /**
     * @var Responses
     */
    private $responses;

    /**
     * Parameters constructor.
     *
     * @param ContainerInterface $container
     * @param \Twig_Environment  $templateEngine
     * @param Responses          $responses
     */
    public function __construct(ContainerInterface $container, \Twig_Environment $templateEngine, Responses $responses)
    {
        $this->container = $container;
        $this->templateEngine = $templateEngine;
        $this->responses = $responses;
    }

    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function process(Operation $operation, RouteModel $routeModel): void
    {
        $action = $routeModel->getMethod();

        if (\in_array($action, [Rest::COUNT_ACTION, Rest::IDS_ACTION], true)) {
            $this->responses->add404($operation);
            $this->addParameterCriteria($operation);
        } elseif (\in_array($action, [Rest::DELETE_ACTION, Rest::PATCH_ACTION, Rest::UPDATE_ACTION], true)) {
            $this->changePathParameter($operation);
        } elseif ($action === Rest::FIND_ONE_ACTION) {
            $this->addParameterPopulate($operation, $routeModel);
            $this->changePathParameter($operation);
        } elseif ($action === Rest::FIND_ACTION) {
            $this->addParameterOrderBy($operation);
            $this->addParameterLimit($operation);
            $this->addParameterOffset($operation);
            $this->addParameterSearch($operation, $routeModel);
            $this->addParameterCriteria($operation);
            $this->addParameterPopulate($operation, $routeModel);
        }
    }

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

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_search.twig',
            [
                'properties' => $searchColumns,
                'examples'   => self::$searchExamples,
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

    /**
     * @param Operation $operation
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterCriteria(Operation $operation): void
    {
        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_criteria.twig',
            [
                'examples' => self::$criteriaExamples,
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

    /**
     * @param Operation $operation
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterOrderBy(Operation $operation): void
    {
        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_order.twig',
            [
                'examples'          => self::$orderByExamples,
                'advancedExamples'  => self::$orderByAdvancedExamples,
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

    /**
     * @param Operation  $operation
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterLimit(Operation $operation): void
    {
        // Specify used  examples for this parameter
        static $examples = [
            '?limit=10',
        ];

        $operation->getParameters()->add(
            $this->getLimitOffsetParameter('limit', 'parameter_limit.twig', $examples)
        );
    }

    /**
     * @param Operation $operation
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function addParameterOffset(Operation $operation): void
    {
        // Specify used  examples for this parameter
        static $examples = [
            '?offset=10',
        ];

        $operation->getParameters()->add(
            $this->getLimitOffsetParameter('offset', 'parameter_offset.twig', $examples)
        );
    }

    /**
     * @param string $name
     * @param string $template
     * @param array  $examples
     *
     * @return Parameter
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getLimitOffsetParameter(string $name, string $template, array $examples): Parameter
    {
        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/' . $template,
            [
                'examples' => $examples,
            ]
        );

        $parameter = [
            'type'          => 'integer',
            'name'          => $name,
            'in'            => 'query',
            'required'      => false,
            'description'   => $description,
            'default'       => 10,
        ];

        return new Parameter($parameter);
    }

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

        // Render a parameter description
        $description = $this->templateEngine->render(
            'Swagger/parameter_populate.twig',
            [
                'examples'     => $this->getPopulateExamples($controller),
                'associations' => $controller->getResource()->getAssociations(),
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

    /**
     * @param Operation $operation
     */
    private function changePathParameter(Operation $operation): void
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

    /**
     * @param Controller $controller
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    private function getPopulateExamples(Controller $controller): array
    {
        // Determine base name for resource serializer group
        $bits = \explode('\\', $controller->getResource()->getEntityName());
        $basename = \end($bits);

        // Specify used  examples for this parameter
        $examples = [];

        foreach ($controller->getResource()->getAssociations() as $association) {
            $examples[] = '?populate[]=' . $basename . '.' . $association;
        }

        $examples[] = '?populate[]=' . $basename . '.property';
        $examples[] = '?populate[]=' . $basename . '.prop1&populate[]=' . $basename . '.prop2';

        return $examples;
    }
}
