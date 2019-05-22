<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/FindMethod.php
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
 * Trait FindMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait FindMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'findMethod' method for REST resources.
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
    public function findMethod(Request $request, ?array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['GET'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Determine used parameters
        $orderBy = RequestHandler::getOrderBy($request);
        $limit = RequestHandler::getLimit($request);
        $offset = RequestHandler::getOffset($request);
        $search = RequestHandler::getSearchTerms($request);

        // Get current resource service
        $resource = $this->getResource();

        try {
            $criteria = RequestHandler::getCriteria($request);

            $this->processCriteria($criteria);

            return $this
                ->getResponseHandler()
                ->createResponse($request, $resource->find($criteria, $orderBy, $limit, $offset, $search), $resource);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
