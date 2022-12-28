<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/User/FindAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\User;

use App\Rest\Traits\Methods\FindMethod;
use App\Security\RolesService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait FindAction
 *
 * Trait to add 'findAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
    #[IsGranted(RolesService::ROLE_USER)]
    public function findAction(Request $request): Response
    {
        return $this->findMethod($request);
    }
}
