<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Summary.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Rest\Controller;
use App\Rest\Doc\RouteModel;
use EXSyst\Component\Swagger\Operation;
use Psr\Container\ContainerInterface;

/**
 * Class Summary
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Summary
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Summary constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
    public function process(Operation $operation, RouteModel $routeModel): void
    {
        $summary = '';

        switch ($routeModel->getMethod()) {
            case Rest::COUNT_ACTION:
                $summary = 'Endpoint action to get count of entities (%s) on this resource. Base route: "%s"';
                break;
            case Rest::CREATE_ACTION:
                $summary = 'Endpoint action to create new entity (%s) to this resource. Base route: "%s"';
                break;
            case Rest::DELETE_ACTION:
                $summary = 'Endpoint action to delete specified entity (%s) from this resource. Base route: "%s"';
                break;
            case Rest::FIND_ACTION:
                $summary = 'Endpoint action to fetch entities (%s) from this resource. Base route: "%s"';
                break;
            case Rest::FIND_ONE_ACTION:
                $summary = 'Endpoint action to fetch specified entity (%s) from this resource. Base route: "%s"';
                break;
            case Rest::IDS_ACTION:
                $summary = 'Endpoint action to fetch entities (%s) id values from this resource. Base route: "%s"';
                break;
            case Rest::PATCH_ACTION:
                $summary = 'Endpoint action to create patch specified entity (%s) on this resource. Base route: "%s"';
                break;
            case Rest::UPDATE_ACTION:
                $summary = 'Endpoint action to create update specified entity (%s) on this resource. Base route: "%s"';
                break;
        }

        $this->processSummary($operation, $routeModel, $summary);
    }

    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     * @param string     $summary
     *
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function processSummary(Operation $operation, RouteModel $routeModel, string $summary): void
    {
        if (!empty($summary) && $this->container->has($routeModel->getController())) {
            /** @var Controller $controller */
            $controller = $this->container->get($routeModel->getController());

            $operation->setSummary(\sprintf(
                $summary,
                $controller->getResource()->getEntityName(),
                $routeModel->getBaseRoute()
            ));
        }
    }
}
