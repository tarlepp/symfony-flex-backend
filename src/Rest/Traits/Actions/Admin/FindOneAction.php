<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Admin/FindOneAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\Admin;

use App\Rest\Traits\Methods\FindOneMethod;
use App\Security\RolesService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
            'id' => '%app.uuid_v1_regex%',
        ],
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(RolesService::ROLE_ADMIN)]
    public function findOneAction(Request $request, string $id): Response
    {
        return $this->findOneMethod($request, $id);
    }
}
