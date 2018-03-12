<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/FindOneMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait FindOneMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait FindOneMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'findOneMethod' method for REST resources.
     *
     * @param Request       $request
     * @param string        $id
     * @param string[]|null $allowedHttpMethods
     *
     * @return Response
     *
     * @throws LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function findOneMethod(Request $request, string $id, ?array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['GET'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            // Fetch data from database
            return $this
                ->getResponseHandler()
                ->createResponse($request, $this->getResource()->findOne($id, true));
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception, $id);
        }
    }
}
