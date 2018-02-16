<?php
declare(strict_types = 1);
/**
 * /src/Rest/Controller.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use App\Rest\Traits\RestMethodHelper;

/**
 * Class Controller
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class Controller implements ControllerInterface
{
    // Traits
    use RestMethodHelper;

    public const METHOD_COUNT      = 'countMethod';
    public const METHOD_CREATE     = 'createMethod';
    public const METHOD_DELETE     = 'deleteMethod';
    public const METHOD_FIND       = 'findMethod';
    public const METHOD_FIND_ONE   = 'findOneMethod';
    public const METHOD_IDS        = 'idsMethod';
    public const METHOD_PATCH      = 'patchMethod';
    public const METHOD_UPDATE     = 'updateMethod';

    /**
     * Method to initialize REST controller.
     *
     * @param RestResourceInterface    $resource
     * @param ResponseHandlerInterface $responseHandler
     */
    protected function init(RestResourceInterface $resource, ResponseHandlerInterface $responseHandler): void
    {
        $this->resource = $resource;
        $this->responseHandler = $responseHandler;

        $this->responseHandler->setResource($this->resource);
    }
}
