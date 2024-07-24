<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/User/IdsAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\User;

use App\Enum\Role;
use App\Rest\Traits\Methods\IdsMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait to add 'idsAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\IdsMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait IdsAction
{
    use IdsMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/ids',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(Role::USER->value)]
    public function idsAction(Request $request): Response
    {
        return $this->idsMethod($request);
    }
}
