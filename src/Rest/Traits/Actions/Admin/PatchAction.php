<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Admin/PatchAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\Admin;

use App\DTO\RestDtoInterface;
use App\Rest\Traits\Methods\PatchMethod;
use App\Security\RolesService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait PatchAction
 *
 * Trait to add 'patchAction' for REST controllers for 'ROLE_ADMIN' users.
 *
 * @see \App\Rest\Traits\Methods\PatchMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Root
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
            'id' => '%app.uuid_v1_regex%',
        ],
        methods: [Request::METHOD_PATCH],
    )]
    #[IsGranted(RolesService::ROLE_ADMIN)]
    public function patchAction(Request $request, RestDtoInterface $restDto, string $id): Response
    {
        return $this->patchMethod($request, $restDto, $id);
    }
}
