<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/CountMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\RequestHandler;
use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CountMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method RestResourceInterface getResource()
 * @method ResponseHandlerInterface getResponseHandler()
 */
trait CountMethod
{
    /**
     * Generic 'countMethod' method for REST resources.
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
    public function countMethod(Request $request, array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['GET'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Determine used parameters
        $search = RequestHandler::getSearchTerms($request);

        try {
            $criteria = RequestHandler::getCriteria($request);

            if (\method_exists($this, 'processCriteria')) {
                $this->processCriteria($criteria);
            }

            return $this
                ->getResponseHandler()
                ->createResponse($request, ['count' => $this->getResource()->count($criteria, $search)]
            );
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
