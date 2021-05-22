<?php
declare(strict_types = 1);
/**
 * /src/Controller/Role/RoleController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\Role;

use App\Resource\RoleResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RoleController
 *
 * @OA\Tag(name="Role Management")
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method RoleResource getResource()
 */
#[Route(
    path: '/role',
)]
#[Security('is_granted("IS_AUTHENTICATED_FULLY")')]
class RoleController extends Controller
{
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\IdsAction;

    public function __construct(
        protected RoleResource $resource,
    ) {
    }
}
