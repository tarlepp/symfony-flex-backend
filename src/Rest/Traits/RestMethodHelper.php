<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/MethodValidator.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Rest\ControllerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Trait MethodValidator
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestMethodHelper
{
    /**
     * Method to validate REST trait method.
     *
     * @param Request $request
     * @param array   $allowedHttpMethods
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function validateRestMethod(Request $request, array $allowedHttpMethods): void
    {
        // Make sure that we have everything we need to make this work
        if (!($this instanceof ControllerInterface)) {
            $message = \sprintf(
                'You cannot use \'%s\' within controller class that does not implement \'%s\'',
                self::class,
                ControllerInterface::class
            );

            throw new \LogicException($message);
        }

        if (!\in_array($request->getMethod(), $allowedHttpMethods, true)) {
            throw new MethodNotAllowedHttpException($allowedHttpMethods);
        }
    }

    /**
     * Method to handle possible REST method trait exception.
     *
     * @param \Exception $exception
     *
     * @return HttpException
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handleRestMethodException(\Exception $exception): HttpException
    {
        if ($exception instanceof HttpException) {
            $output = $exception;
        } elseif ($exception instanceof NoResultException) {
            $code = Response::HTTP_NOT_FOUND;

            $output = new HttpException($code, 'Not found', $exception, [], $code);
        } elseif ($exception instanceof NonUniqueResultException) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;

            $output = new HttpException($code, $exception->getMessage(), $exception, [], $code);
        } else {
            $code = $exception->getCode() !== 0 ? $exception->getCode() : Response::HTTP_BAD_REQUEST;

            $output = new HttpException($code, $exception->getMessage(), $exception, [], $code);
        }

        return $output;
    }
}