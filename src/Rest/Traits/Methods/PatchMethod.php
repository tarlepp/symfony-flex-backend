<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/PatchMethod.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait PatchMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait PatchMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'patchMethod' method for REST resources.
     *
     * @param Request          $request
     * @param RestDtoInterface $restDto
     * @param string           $id
     * @param string[]|null    $allowedHttpMethods
     *
     * @return Response
     *
     * @throws Throwable
     */
    public function patchMethod(
        Request $request,
        RestDtoInterface $restDto,
        string $id,
        ?array $allowedHttpMethods = null
    ): Response {
        $allowedHttpMethods = $allowedHttpMethods ?? ['PATCH'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Get current resource service
        $resource = $this->getResource();

        try {
            $data = $resource->patch($id, $restDto, true);

            return $this->getResponseHandler()->createResponse($request, $data, $resource);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception, $id);
        }
    }
}
