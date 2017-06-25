<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/CreateMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\ControllerInterface;
use App\Rest\DTO\RestDtoInterface;
use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Trait CreateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CreateMethod
{
    /**
     * Generic 'createMethod' method for REST resources.
     *
     * @param Request    $request
     * @param array|null $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createMethod(Request $request, array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['POST'];

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

        try {
            // TODO: how to override this in controller ?
            $dtoClass = $this->getResource()->getDtoClass();

            /** @var RestDtoInterface $dto */
            $dto = $this->getResponseHandler()->getSerializer()->deserialize($request->getContent(), $dtoClass, 'json');

            return $this->getResponseHandler()->createResponse($request, $this->getResource()->create($dto), Response::HTTP_CREATED);
        } catch (\Exception $error) {
            if ($error instanceof HttpException) {
                throw $error;
            }

            $code = Response::HTTP_BAD_REQUEST;

            throw new HttpException($code, $error->getMessage(), $error, [], $code);
        }
    }

    /**
     * Getter method for resource service.
     *
     * @return ResourceInterface
     */
    abstract public function getResource(): ResourceInterface;

    /**
     * @return ResponseHandlerInterface
     */
    abstract public function getResponseHandler(): ResponseHandlerInterface;
}
