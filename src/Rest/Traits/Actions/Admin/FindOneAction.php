<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Admin/FindOneAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\Admin;

use App\Enum\Role;
use App\Rest\Traits\Methods\FindOneMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait FindOneAction
 *
 * Trait to add 'findOneAction' for REST controllers for 'ROLE_ADMIN' users.
 *
 * @see \App\Rest\Traits\Methods\FindOneMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Admin
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
    #[IsGranted(Role::ADMIN->value)]
    public function findOneAction(Request $request, string $id): Response
    {
        return $this->findOneMethod($request, $id);
    }
}
