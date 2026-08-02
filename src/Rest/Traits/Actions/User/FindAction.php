<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/Actions/User/FindAction.php
 */

namespace App\Rest\Traits\Actions\User;

use App\Enum\Role;
use App\Rest\Traits\Methods\FindMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait to add 'findAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 */
trait FindAction
{
    use FindMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(Role::USER->value)]
    public function findAction(Request $request): Response
    {
        return $this->findMethod($request);
    }
}
