<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/FindMethod.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
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
     * @throws Throwable
     */
    public function findMethod(Request $request, ?array $allowedHttpMethods = null): Response
    {
        $resource = $this->getResourceForMethod($request, $allowedHttpMethods ?? ['GET']);

        // Determine used parameters
        $orderBy = RequestHandler::getOrderBy($request);
        $limit = RequestHandler::getLimit($request);
        $offset = RequestHandler::getOffset($request);
        $search = RequestHandler::getSearchTerms($request);

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
