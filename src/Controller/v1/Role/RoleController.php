<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/Role/RoleController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\Role;

use App\Resource\RoleResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class RoleController
 *
 * @OA\Tag(name="Role Management")
 *
 * @package App\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method RoleResource getResource()
 */
#[Route(
    path: '/v1/role',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
class RoleController extends Controller
{
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\IdsAction;

    public function __construct(
        RoleResource $resource,
    ) {
        parent::__construct($resource);
    }
}
