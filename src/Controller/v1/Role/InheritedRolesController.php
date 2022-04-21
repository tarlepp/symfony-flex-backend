<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/Role/InheritedRolesController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\Role;

use App\Entity\Role;
use App\Enum\Role as RoleEnum;
use App\Resource\RoleResource;
use App\Security\RolesService;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InheritedRolesController
 *
 * @OA\Tag(name="Role Management")
 *
 * @package App\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class InheritedRolesController
{
    public function __construct(
        private RolesService $rolesService,
    ) {
    }

    /**
     * Endpoint action to return all inherited roles as an array for specified
     * Role.
     *
     * @OA\Parameter(
     *      name="Authorization",
     *      in="header",
     *      required=true,
     *      description="Authorization header",
     *      @OA\Schema(
     *          type="string",
     *          default="Bearer _your_jwt_here_",
     *      ),
     *  )
     * @OA\Response(
     *      response=200,
     *      description="Inherited roles",
     *      @OA\Schema(
     *          type="array",
     *          @OA\Items(
     *              type="string",
     *          ),
     *      ),
     *  )
     * @OA\Response(
     *      response=401,
     *      description="Invalid token",
     *      @OA\Schema(
     *          type="object",
     *          example={
     *              "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *              "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *          },
     *          @OA\Property(property="code", type="integer", description="Error code"),
     *          @OA\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     */
    #[Route(
        path: '/v1/role/{role}/inherited',
        requirements: [
            'role' => '^ROLE_\w+$',
        ],
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(RoleEnum::ADMIN)]
    #[ParamConverter(
        data: 'role',
        class: RoleResource::class,
    )]
    public function __invoke(Role $role): JsonResponse
    {
        return new JsonResponse($this->rolesService->getInheritedRoles([$role->getId()]));
    }
}
