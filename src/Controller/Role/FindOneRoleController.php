<?php
declare(strict_types = 1);
/**
 * /src/Controller/Role/FindOneRoleController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\Role;

use App\Resource\RoleResource;
use App\Rest\Controller;
use App\Rest\Traits\Methods;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Throwable;

/**
 * Class FindOneRoleController
 *
 * @OA\Tag(name="Role Management")
 *
 * @package App\Controller\Role
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
        path: '/role/{role}',
        requirements: ['role' => '^ROLE_\w+$'],
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(Request $request, string $role): Response
    {
        return $this->findOneMethod($request, $role);
    }
}
