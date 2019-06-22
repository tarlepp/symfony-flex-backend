<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/AbstractGenericMethods.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use App\Rest\ResponseHandlerInterface;
use App\Rest\RestResourceInterface;
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
     * @param Request $request
     * @param array   $allowedHttpMethods
     *
     * @return RestResourceInterface
     *
     * @throws Throwable
     */
    abstract public function validateRestMethodAndGetResource(
        Request $request,
        array $allowedHttpMethods
    ): RestResourceInterface;

    /**
     * @return ResponseHandlerInterface
     *
     * @throws UnexpectedValueException
     */
    abstract public function getResponseHandler(): ResponseHandlerInterface;

    /**
     * Method to process current criteria array.
     *
     * @param mixed[] $criteria
     */
    abstract public function processCriteria(array &$criteria): void;

    /**
     * Method to handle possible REST method trait exception.
     *
     * @param Throwable   $exception
     * @param string|null $id
     *
     * @return Throwable
     *
     * @throws HttpException
     */
    abstract public function handleRestMethodException(Throwable $exception, ?string $id = null): Throwable;
}
