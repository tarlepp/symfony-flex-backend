<?php
declare(strict_types = 1);
/**
 * /src/Controller/User/UserRolesController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\User;

use App\Entity\User;
use App\Security\RolesService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserRolesController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserRolesController
{
    private RolesService $rolesService;

    /**
     * UserRolesController constructor.
     */
    public function __construct(RolesService $rolesService)
    {
        $this->rolesService = $rolesService;
    }

    /**
     * Endpoint action to fetch specified user roles.
     *
     * @Route(
     *      "/user/{requestUser}/roles",
     *      requirements={
     *          "requestUser" = "%app.uuid_v1_regex%",
     *      },
     *      methods={"GET"},
     *  )
     *
     * @ParamConverter(
     *     "requestUser",
     *     class="App\Resource\UserResource"
     *  )
     *
     * @Security("is_granted('IS_USER_HIMSELF', requestUser) or is_granted('ROLE_ROOT')")
     *
     * @SWG\Tag(name="User Management")
     * @SWG\Parameter(
     *      type="string",
     *      name="Authorization",
     *      in="header",
     *      required=true,
     *      description="Authorization header",
     *      default="Bearer _your_jwt_here_",
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="Specified user roles",
     * @SWG\Schema(
     *          type="array",
     * @SWG\Items(type="string"),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Unauthorized",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     * @SWG\Schema(
     *          type="object",
     * @SWG\Property(property="code", type="integer", description="Error code"),
     * @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @SWG\Response(
     *      response=403,
     *      description="Access denied",
     *      examples={
     *          "Access denied": "{code: 403, message: 'Access denied'}",
     *      },
     * @SWG\Schema(
     *          type="object",
     * @SWG\Property(property="code", type="integer", description="Error code"),
     * @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     */
    public function __invoke(User $requestUser): JsonResponse
    {
        return new JsonResponse($this->rolesService->getInheritedRoles($requestUser->getRoles()));
    }
}
