<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/Actions/Anon/PatchAction.php
 */

namespace App\Rest\Traits\Actions\Anon;

use App\DTO\RestDtoInterface;
use App\Rest\Traits\Methods\PatchMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Throwable;

/**
 * Trait to add 'patchAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\PatchMethod for detailed documents.
 */
trait PatchAction
{
    use PatchMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/{id}',
        requirements: [
            'id' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_PATCH],
    )]
    public function patchAction(Request $request, RestDtoInterface $restDto, string $id): Response
    {
        return $this->patchMethod($request, $restDto, $id);
    }
}
