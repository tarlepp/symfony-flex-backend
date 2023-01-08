<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Logged/DeleteAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\Logged;

use App\Enum\Role;
use App\Rest\Traits\Methods\DeleteMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait DeleteAction
 *
 * Trait to add 'deleteAction' for REST controllers for 'ROLE_LOGGED' users.
 *
 * @see \App\Rest\Traits\Methods\DeleteMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Logged
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait DeleteAction
{
    use DeleteMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/{id}',
        requirements: [
            'id' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(Role::LOGGED->value)]
    public function deleteAction(Request $request, string $id): Response
    {
        return $this->deleteMethod($request, $id);
    }
}
