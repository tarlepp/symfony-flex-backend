<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/DeleteMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait DeleteMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait DeleteMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'deleteMethod' method for REST resources.
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
    public function deleteMethod(Request $request, string $id, array $allowedHttpMethods = null): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['DELETE'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            // Fetch data from database
            return $this
                ->getResponseHandler()
                ->createResponse($request, $this->getResource()->delete($id));
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception, $id);
        }
    }
}
