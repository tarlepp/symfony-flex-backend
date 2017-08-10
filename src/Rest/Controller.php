<?php
declare(strict_types=1);
/**
 * /src/Rest/Controller.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use App\Rest\DTO\RestDtoInterface;
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

    /**
     * Method + DTO class names (key + value)
     *
     * @var string[]
     */
    protected static $dtoClasses = [];

    /**
     * Method + Form type class names (key + value)
     *
     * @var string[]
     */
    protected static $formTypes = [];

    /**
     * @var RestResourceInterface
     */
    protected $resource;

    /**
     * @var ResponseHandlerInterface
     */
    protected $responseHandler;

    /**
     * @return RestResourceInterface
     *
     * @throws \UnexpectedValueException
     */
    public function getResource(): RestResourceInterface
    {
        if (!$this->resource instanceof RestResourceInterface) {
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
            throw new \UnexpectedValueException('ResponseHandler service not set', 500);
        }

        return $this->responseHandler;
    }

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function getDtoClass(string $method = null): string
    {
        $dtoClass = \array_key_exists($method, static::$dtoClasses)
            ? static::$dtoClasses[$method]
            : $this->getResource()->getDtoClass();

        if (!\in_array(RestDtoInterface::class, \class_implements($dtoClass), true)) {
            $message = \sprintf(
                'Given DTO class \'%s\' is not implementing \'%s\' interface.',
                $dtoClass,
                RestDtoInterface::class
            );

            throw new \UnexpectedValueException($message);
        }

        return $dtoClass;
    }

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function getFormTypeClass(string $method = null): string
    {
        $method = $method ?? '';

        if ($position = mb_strrpos($method, '::')) {
            $method = mb_substr($method, $position + 2);
        }

        $formTypeClass = \array_key_exists($method, static::$formTypes)
            ? static::$formTypes[$method]
            : $this->getResource()->getFormTypeClass();

        return $formTypeClass;
    }

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
