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
     *
     * @throws \UnexpectedValueException
     */
    public function getResource(): ResourceInterface
    {
        if (!$this->resource instanceof ResourceInterface) {
            throw new \UnexpectedValueException('Resource service not set', 500);
        }

        return $this->resource;
    }

    /**
     * @return ResponseHandlerInterface
     *
     * @throws \UnexpectedValueException
     */
    public function getResponseHandler(): ResponseHandlerInterface
    {
        if (!$this->responseHandler instanceof ResponseHandlerInterface) {
            throw new \UnexpectedValueException('Response handler not set', 500);
        }

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
