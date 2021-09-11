<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/UserGroup/UsersController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\UserGroup;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Rest\ResponseHandler;
use App\Security\RolesService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class UsersController
 *
 * @package App\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UsersController
{
    public function __construct(
        private UserResource $userResource,
        private ResponseHandler $responseHandler,
    ) {
    }

    /**
     * Endpoint action to list specified user group users.
     *
     * @OA\Tag(name="UserGroup Management")
     * @OA\Parameter(
     *      name="Authorization",
     *      in="header",
     *      required=true,
     *      description="Authorization header",
     *      @OA\Schema(
     *          type="string",
     *          default="Bearer _your_jwt_here_",
     *      )
     *  )
     * @OA\Response(
     *      response=200,
     *      description="User group users",
     * @OA\Schema(
     *          ref=@Model(
     *              type=User::class,
     *              groups={"User", "User.userGroups", "User.roles", "UserGroup", "UserGroup.role"},
     *          ),
     *      ),
     *  )
     * @OA\Response(
     *      response=401,
     *      description="Invalid token",
     *      @OA\Schema(
     *          example={
     *              "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *              "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *          },
     *      ),
     *  )
     * @OA\Response(
     *      response=404,
     *      description="User Group not found",
     *  )
     *
     * @throws Throwable
     */
    #[Route(
        path: '/v1/user_group/{userGroup}/users',
        requirements: [
            'userGroup' => '%app.uuid_v1_regex%',
        ],
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(RolesService::ROLE_ADMIN)]
    #[ParamConverter(
        data: 'userGroup',
        class: UserGroupResource::class,
    )]
    public function __invoke(Request $request, UserGroup $userGroup): Response
    {
        return $this->responseHandler
            ->createResponse($request, $this->userResource->getUsersForGroup($userGroup), $this->userResource);
    }
}
