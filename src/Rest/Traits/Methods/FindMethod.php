<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/FindMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @param Request    $request
     * @param array|null $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function findMethod(Request $request, array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['GET'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Determine used parameters
        $orderBy    = RequestHandler::getOrderBy($request);
        $limit      = RequestHandler::getLimit($request);
        $offset     = RequestHandler::getOffset($request);
        $search     = RequestHandler::getSearchTerms($request);

        try {
            $criteria = RequestHandler::getCriteria($request);

            if (\method_exists($this, 'processCriteria')) {
                $this->processCriteria($criteria);
            }

            return $this
                ->getResponseHandler()
                ->createResponse($request, $this->getResource()->find($criteria, $orderBy, $limit, $offset, $search));
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
