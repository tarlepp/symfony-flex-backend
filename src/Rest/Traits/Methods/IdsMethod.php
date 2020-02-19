<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/FindOneMethod.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait IdsMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait IdsMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'idsMethod' method for REST resources.
     *
     * @param Request       $request
     * @param string[]|null $allowedHttpMethods
     *
     * @return Response
     *
     * @throws Throwable
     */
    public function idsMethod(Request $request, ?array $allowedHttpMethods = null): Response
    {
        $resource = $this->getResourceForMethod($request, $allowedHttpMethods ?? ['GET']);

        // Determine used parameters
        $search = RequestHandler::getSearchTerms($request);

        try {
            $criteria = RequestHandler::getCriteria($request);

            $this->processCriteria($criteria);

            return $this
                ->getResponseHandler()
                ->createResponse($request, $resource->getIds($criteria, $search), $resource);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
