<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/CountMethod.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait CountMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CountMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'countMethod' method for REST resources.
     *
     * @param Request       $request
     * @param string[]|null $allowedHttpMethods
     *
     * @return Response
     *
     * @throws LogicException
     * @throws Throwable
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function countMethod(Request $request, ?array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['GET'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Determine used parameters
        $search = RequestHandler::getSearchTerms($request);

        // Get current resource service
        $resource = $this->getResource();

        try {
            $criteria = RequestHandler::getCriteria($request);

            $this->processCriteria($criteria);

            return $this
                ->getResponseHandler()
                ->createResponse($request, ['count' => $resource->count($criteria, $search)], $resource);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
