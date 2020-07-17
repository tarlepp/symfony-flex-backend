<?php
declare(strict_types = 1);
/**
 * /src/Controller/User/DetachUserGroupController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class DetachUserGroupController
 *
 * @package App\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DetachUserGroupController
{
    private SerializerInterface $serializer;
    private UserResource $userResource;

    /**
     * DetachUserGroupController constructor.
     */
    public function __construct(SerializerInterface $serializer, UserResource $userResource)
    {
        $this->serializer = $serializer;
        $this->userResource = $userResource;
    }

    /**
     * Endpoint action to detach specified user group from specified user.
     *
     * @Route(
     *      "/user/{user}/group/{userGroup}",
     *      requirements={
     *          "user" = "%app.uuid_v1_regex%",
     *          "userGroup" = "%app.uuid_v1_regex%",
     *      },
     *      methods={"DELETE"},
     *  )
     *
     * @ParamConverter(
     *      "user",
     *      class="App\Resource\UserResource",
     *  )
     * @ParamConverter(
     *      "userGroup",
     *      class="App\Resource\UserGroupResource",
     *  )
     *
     * @Security("is_granted('ROLE_ROOT')")
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
     * @SWG\Parameter(
     *      type="string",
     *      name="userId",
     *      in="path",
     *      required=true,
     *      description="User GUID",
     *      default="User GUID",
     *  )
     * @SWG\Parameter(
     *      type="string",
     *      name="userGroupId",
     *      in="path",
     *      required=true,
     *      description="User Group GUID",
     *      default="User Group GUID",
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="User groups",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(
     *              ref=@Model(
     *                  type=App\Entity\UserGroup::class,
     *                  groups={"UserGroup", "UserGroup.role"},
     *              ),
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Unauthorized",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @SWG\Response(
     *      response=403,
     *      description="Forbidden",
     *      examples={
     *          "Access denied": "{code: 403, message: 'Access denied'}",
     *      },
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )

     *
     * @throws Throwable
     */
    public function __invoke(User $user, UserGroup $userGroup): JsonResponse
    {
        $this->userResource->save($user->removeUserGroup($userGroup));

        $groups = [
            'groups' => [
                'set.UserGroupBasic',
            ],
        ];

        return new JsonResponse(
            $this->serializer->serialize($user->getUserGroups()->getValues(), 'json', $groups),
            200,
            [],
            true
        );
    }
}
