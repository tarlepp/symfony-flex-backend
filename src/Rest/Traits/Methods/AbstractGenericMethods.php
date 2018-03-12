<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/AbstractGenericMethods.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\ResponseHandlerInterface;
use App\Rest\RestResourceInterface;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use UnexpectedValueException;

/**
 * Trait AbstractGenericMethods
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait AbstractGenericMethods
{
    /**
     * @return RestResourceInterface
     *
     * @throws UnexpectedValueException
     */
    abstract public function getResource(): RestResourceInterface;

    /**
     * @return ResponseHandlerInterface
     *
     * @throws UnexpectedValueException
     */
    abstract public function getResponseHandler(): ResponseHandlerInterface;

    /**
     * Method to validate REST trait method.
     *
     * @param Request  $request
     * @param string[] $allowedHttpMethods
     *
     * @throws LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    abstract public function validateRestMethod(Request $request, array $allowedHttpMethods): void;

    /**
     * Method to process current criteria array.
     *
     * @param mixed[] &$criteria
     */
    abstract public function processCriteria(array &$criteria): void;

    /**
     * Method to handle possible REST method trait exception.
     *
     * @param Throwable   $exception
     * @param string|null $id
     *
     * @return HttpException
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    abstract public function handleRestMethodException(Throwable $exception, ?string $id = null): HttpException;
}
