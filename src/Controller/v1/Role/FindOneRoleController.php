<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/Role/FindOneRoleController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\Role;

use App\Enum\Role;
use App\Resource\RoleResource;
use App\Rest\Controller;
use App\Rest\Traits\Methods;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class FindOneRoleController
 *
 * @OA\Tag(name="Role Management")
 *
 * @package App\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneRoleController extends Controller
{
    use Methods\FindOneMethod;

    public function __construct(
        protected RoleResource $resource,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/v1/role/{role}',
        requirements: [
            'role' => '^ROLE_\w+$',
        ],
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(Role::ADMIN)]
    public function __invoke(Request $request, string $role): Response
    {
        return $this->findOneMethod($request, $role);
    }
}
