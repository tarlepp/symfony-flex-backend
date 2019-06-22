<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/UpdateMethod.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait UpdateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UpdateMethod
{
    // Traits
    use AbstractGenericMethods;

    /**
     * Generic 'updateMethod' method for REST resources.
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
    public function updateMethod(
        Request $request,
        RestDtoInterface $restDto,
        string $id,
        ?array $allowedHttpMethods = null
    ): Response {
        $resource = $this->validateRestMethodAndGetResource($request, $allowedHttpMethods ?? ['PUT']);

        try {
            $data = $resource->update($id, $restDto, true);

            return $this->getResponseHandler()->createResponse($request, $data, $resource);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception, $id);
        }
    }
}
