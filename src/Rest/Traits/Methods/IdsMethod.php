<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/Methods/IdsMethod.php
 */

namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait IdsMethod
{
    /**
     * Generic 'idsMethod' method for REST resources.
     *
     * @param array<int, string>|null $allowedHttpMethods
     *
     * @throws Throwable
     */
    public function idsMethod(Request $request, ?array $allowedHttpMethods = null): Response
    {
        $resource = $this->getResourceForMethod($request, $allowedHttpMethods ?? [Request::METHOD_GET]);

        // Determine used parameters
        $search = RequestHandler::getSearchTerms($request);

        try {
            $criteria = RequestHandler::getCriteria($request);

            $this->processCriteria($criteria, $request, __METHOD__);

            $ids = $resource->getIds($criteria, $search);

            return $this
                ->getResponseHandler()
                ->createResponse($request, $ids, $resource);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
