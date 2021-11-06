<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/UserGroup/DetachUserController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\UserGroup;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Security\RolesService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class DetachUserController
 *
 * @package App\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DetachUserController
{
    public function __construct(
        private UserResource $userResource,
        private UserGroupResource $userGroupResource,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * Endpoint action to detach specified user from specified user group.
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
     * @OA\Parameter(
     *      name="userGroupId",
     *      in="path",
     *      required=true,
     *      description="User Group GUID",
     *      @OA\Schema(
     *          type="string",
     *          default="User Group GUID",
     *      )
     *  )
     * @OA\Parameter(
     *      name="userId",
     *      in="path",
     *      required=true,
     *      description="User GUID",
     *      @OA\Schema(
     *          type="string",
     *          default="User GUID",
     *      )
     *  )
     * @OA\Response(
     *      response=200,
     *      description="Users",
     * @OA\Schema(
     *          type="array",
     * @OA\Items(
     *              ref=@Model(
     *                  type=\App\Entity\User::class,
     *                  groups={"User"},
     *              ),
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
     *      response=403,
     *      description="Access denied",
     *  )
     *
     * @throws Throwable
     */
    #[Route(
        path: '/v1/user_group/{userGroup}/user/{user}',
        requirements: [
            'userGroup' => '%app.uuid_v1_regex%',
            'user' => '%app.uuid_v1_regex%',
        ],
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(RolesService::ROLE_ROOT)]
    #[ParamConverter(
        data: 'userGroup',
        class: UserGroupResource::class,
    )]
    #[ParamConverter(
        data: 'user',
        class: UserResource::class,
    )]
    public function __invoke(UserGroup $userGroup, User $user): JsonResponse
    {
        $this->userGroupResource->save($userGroup->removeUser($user), false);
        $this->userResource->save($user, true, true);

        $groups = [
            'groups' => [
                User::SET_USER_BASIC,
            ],
        ];

        return new JsonResponse(
            $this->serializer->serialize($userGroup->getUsers()->getValues(), 'json', $groups),
            json: true,
        );
    }
}
