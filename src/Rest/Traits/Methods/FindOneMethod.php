<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/FindOneMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait FindOneMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method ResourceInterface getResource()
 * @method ResponseHandlerInterface getResponseHandler()
 */
trait FindOneMethod
{
    /**
     * Generic 'findOneMethod' method for REST resources.
     *
     * @param Request    $request
     * @param string     $id
     * @param array|null $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function findOneMethod(Request $request, string $id, array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['GET'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            // Fetch data from database
            return $this
                ->getResponseHandler()
                ->createResponse($request, $this->getResource()->findOne($id, true));
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
