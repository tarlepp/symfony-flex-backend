<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/CountMethod.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait CountMethod
{
    /**
     * Generic 'countMethod' method for REST resources.
     *
     * @param array<int, string>|null $allowedHttpMethods
     *
     * @throws Throwable
     */
    public function countMethod(Request $request, ?array $allowedHttpMethods = null): Response
    {
        $resource = $this->getResourceForMethod($request, $allowedHttpMethods ?? [Request::METHOD_GET]);

        // Determine used parameters
        $search = RequestHandler::getSearchTerms($request);

        try {
            $criteria = RequestHandler::getCriteria($request);

            $this->processCriteria($criteria, $request, __METHOD__);

            return $this
                ->getResponseHandler()
                ->createResponse(
                    $request,
                    [
                        'count' => $resource->count($criteria, $search),
                    ],
                    $resource
                );
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
