<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/Actions/Admin/CountAction.php
 */

namespace App\Rest\Traits\Actions\Admin;

use App\Enum\Role;
use App\Rest\Traits\Methods\CountMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait to add 'countAction' for REST controllers for 'ROLE_ADMIN' users.
 *
 * @see \App\Rest\Traits\Methods\CountMethod for detailed documents.
 */
trait CountAction
{
    use CountMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/count',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(Role::ADMIN->value)]
    public function countAction(Request $request): Response
    {
        return $this->countMethod($request);
    }
}
