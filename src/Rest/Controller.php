<?php
declare(strict_types=1);
/**
 * /src/Rest/Controller.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

/**
 * Class Controller
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class Controller implements ControllerInterface
{
    /**
     * @var ResourceInterface
     */
    protected $resource;

    /**
     * @var ResponseHandlerInterface
     */
    protected $responseHandler;

    /**
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    /**
     * @return ResponseHandlerInterface
     */
    public function getResponseHandler(): ResponseHandlerInterface
    {
        return $this->responseHandler;
    }

    /**
     * Method to initialize REST controller.
     *
     * @param ResourceInterface       $resource
     * @param ResponseHandlerInterface $responseHandler
     */
    protected function init(ResourceInterface $resource, ResponseHandlerInterface $responseHandler): void
    {
        $this->resource = $resource;
        $this->responseHandler = $responseHandler;

        $this->responseHandler->setResource($this->resource);
    }
}
