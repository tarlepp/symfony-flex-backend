<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/Actions/Anon/FindOneAction.php
 */

namespace App\Rest\Traits\Actions\Anon;

use App\Rest\Traits\Methods\FindOneMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Throwable;

/**
 * Trait to add 'findOneAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\FindOneMethod for detailed documents.
 */
trait FindOneAction
{
    use FindOneMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/{id}',
        requirements: [
            'id' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_GET],
    )]
    public function findOneAction(Request $request, string $id): Response
    {
        return $this->findOneMethod($request, $id);
    }
}
