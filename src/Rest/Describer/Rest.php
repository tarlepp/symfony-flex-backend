<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Rest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Rest\Doc\RouteModel;
use EXSyst\Component\Swagger\Operation;

/**
 * Class Rest
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Rest
{
    public const COUNT_ACTION    = 'countAction';
    public const CREATE_ACTION   = 'createAction';
    public const DELETE_ACTION   = 'deleteAction';
    public const FIND_ACTION     = 'findAction';
    public const FIND_ONE_ACTION = 'findOneAction';
    public const IDS_ACTION      = 'idsAction';
    public const PATCH_ACTION    = 'patchAction';
    public const UPDATE_ACTION   = 'updateAction';

    /**
     * @var Tags
     */
    private $tags;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var Summary
     */
    private $summary;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * ApiDocDescriberRest constructor.
     *
     * @param Tags       $tags
     * @param Security   $security
     * @param Summary    $summary
     * @param Response   $response
     * @param Parameters $parameters
     */
    public function __construct(
        Tags $tags,
        Security $security,
        Summary $summary,
        Response $response,
        Parameters $parameters
    ) {
        $this->tags = $tags;
        $this->security = $security;
        $this->summary = $summary;
        $this->response = $response;
        $this->parameters = $parameters;
    }

    /**
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws \UnexpectedValueException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function createDocs(Operation $operation, RouteModel $routeModel): void
    {
        $this->tags->process($operation, $routeModel);
        $this->security->process($operation, $routeModel);
        $this->summary->process($operation, $routeModel);
        $this->response->process($operation, $routeModel);
        $this->parameters->process($operation, $routeModel);
    }
}
