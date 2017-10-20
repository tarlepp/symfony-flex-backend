<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Response.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Rest\Controller;
use App\Rest\Doc\RouteModel;
use EXSyst\Component\Swagger\Operation;
use Psr\Container\ContainerInterface;

/**
 * Class Response
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Response
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Responses
     */
    private $responses;

    /**
     * Response constructor.
     *
     * @param ContainerInterface $container
     * @param Responses          $responses
     */
    public function __construct(ContainerInterface $container, Responses $responses)
    {
        $this->container = $container;
        $this->responses = $responses;
    }

    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function process(Operation $operation, RouteModel $routeModel): void
    {
        $description = '';
        $statusCode = 200;
        $responses = [];

        switch ($routeModel->getMethod()) {
            case Rest::COUNT_ACTION:
                $description = 'Count of (%s) entities';
                break;
            case Rest::CREATE_ACTION:
                $description = 'Created new entity (%s)';
                $statusCode = 201;
                break;
            case Rest::DELETE_ACTION:
                $description = 'Deleted entity (%s)';
                $responses[] = 'add404';
                break;
            case Rest::FIND_ACTION:
                $description = 'Array of fetched entities (%s)';
                break;
            case Rest::FIND_ONE_ACTION:
                $description = 'Fetched entity (%s)';
                $responses[] = 'add404';
                break;
            case Rest::IDS_ACTION:
                $description = 'Fetched entities (%s) primary key values';
                break;
            case Rest::PATCH_ACTION:
                $description = 'Deleted entity (%s)';
                $responses[] = 'add404';
                break;
            case Rest::UPDATE_ACTION:
                $description = 'Updated entity (%s)';
                $responses[] = 'add404';
                break;
        }

        if (!empty($description) && $this->container->has($routeModel->getController())) {
            /** @var Controller $controller */
            $controller = $this->container->get($routeModel->getController());

            $this->responses->addOk($operation, $description, $statusCode, $controller->getResource()->getEntityName());

            foreach ($responses as $method) {
                $this->responses->$method($operation, $routeModel);
            }
        }
    }
}
