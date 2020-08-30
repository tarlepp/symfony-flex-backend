<?php
declare(strict_types = 1);
/**
 * /src/Rest/Interfaces/ControllerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Interfaces;

use App\Rest\ResponseHandler;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use UnexpectedValueException;

/**
 * Interface ControllerInterface
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ControllerInterface
{
    /**
     * Getter method for `resource` service.
     *
     * @throws UnexpectedValueException
     */
    public function getResource(): RestResourceInterface;

    /**
     * Getter method for `ResponseHandler` service.
     *
     * @throws UnexpectedValueException
     */
    public function getResponseHandler(): ResponseHandlerInterface;

    /**
     * Setter method for `ResponseHandler` service, this is called by Symfony
     * DI.
     *
     * @see https://symfony.com/doc/current/service_container/autowiring.html#autowiring-other-methods-e-g-setters
     *
     * @required
     */
    public function setResponseHandler(ResponseHandler $responseHandler): self;

    /**
     * Getter method for used DTO class for current controller.
     *
     * @throws UnexpectedValueException
     */
    public function getDtoClass(?string $method = null): string;

    /**
     * Method to validate REST trait method.
     *
     * @param array<int, string> $allowedHttpMethods
     *
     * @throws LogicException
     * @throws MethodNotAllowedHttpException
     */
    public function validateRestMethod(Request $request, array $allowedHttpMethods): void;

    /**
     * Method to handle possible REST method trait exception.
     */
    public function handleRestMethodException(Throwable $exception, ?string $id = null): Throwable;

    /**
     * @param array<int, string> $allowedHttpMethods
     */
    public function getResourceForMethod(Request $request, array $allowedHttpMethods): RestResourceInterface;

    /**
     * Method to process current criteria array.
     *
     * @param array<int|string, string|array> $criteria
     */
    public function processCriteria(array &$criteria, Request $request, string $method): void;
}
