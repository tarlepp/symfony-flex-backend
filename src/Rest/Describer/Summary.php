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
use UnexpectedValueException;
use function in_array;
use function sprintf;

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
     * @throws UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function process(Operation $operation, RouteModel $routeModel): void
    {
        [$action, $summary] = $this->getDefaults($routeModel);

        if (in_array(
            $action,
            [Rest::COUNT_ACTION, Rest::FIND_ACTION, Rest::FIND_ONE_ACTION, Rest::IDS_ACTION],
            true
        )) {
            $this->processSummaryForRead($action, $summary);
        } elseif (in_array(
            $action,
            [Rest::CREATE_ACTION, Rest::DELETE_ACTION, Rest::PATCH_ACTION, Rest::UPDATE_ACTION],
            true
        )) {
            $this->processSummaryForWrite($action, $summary);
        }

        $this->processSummary($operation, $routeModel, $summary);
    }

    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     * @param string     $summary
     *
     * @throws UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function processSummary(Operation $operation, RouteModel $routeModel, string $summary): void
    {
        if (!empty($summary) && $this->container->has($routeModel->getController())) {
            /** @var Controller $controller */
            $controller = $this->container->get($routeModel->getController());

            $operation->setSummary(sprintf(
                $summary,
                $controller->getResource()->getEntityName(),
                $routeModel->getBaseRoute()
            ));
        }
    }

    /**
     * @param RouteModel $routeModel
     *
     * @return string[]
     */
    private function getDefaults(RouteModel $routeModel): array
    {
        $action = $routeModel->getMethod();
        $description = '';

        return [$action, $description];
    }

    /**
     * @param string $action
     * @param string &$summary
     */
    private function processSummaryForRead(string $action, string &$summary): void
    {
        if ($action === Rest::COUNT_ACTION) {
            $summary = 'Endpoint action to get count of entities (%s) on this resource. Base route: "%s"';
        } elseif ($action === Rest::FIND_ACTION) {
            $summary = 'Endpoint action to fetch entities (%s) from this resource. Base route: "%s"';
        } elseif ($action === Rest::FIND_ONE_ACTION) {
            $summary = 'Endpoint action to fetch specified entity (%s) from this resource. Base route: "%s"';
        } elseif ($action === Rest::IDS_ACTION) {
            $summary = 'Endpoint action to fetch entities (%s) id values from this resource. Base route: "%s"';
        }
    }

    /**
     * @param string $action
     * @param string &$summary
     */
    private function processSummaryForWrite(string $action, string &$summary): void
    {
        if ($action === Rest::CREATE_ACTION) {
            $summary = 'Endpoint action to create new entity (%s) to this resource. Base route: "%s"';
        } elseif ($action === Rest::DELETE_ACTION) {
            $summary = 'Endpoint action to delete specified entity (%s) from this resource. Base route: "%s"';
        } elseif ($action === Rest::PATCH_ACTION) {
            $summary = 'Endpoint action to create patch specified entity (%s) on this resource. Base route: "%s"';
        } elseif ($action === Rest::UPDATE_ACTION) {
            $summary = 'Endpoint action to create update specified entity (%s) on this resource. Base route: "%s"';
        }
    }
}
