<?php
declare(strict_types = 1);
/**
 * /src/Controller/UserController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller;

use App\Annotation\RestApiDoc;
use App\DTO\User\UserCreate;
use App\DTO\User\UserPatch;
use App\DTO\User\UserUpdate;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use App\Rest\Traits\Methods;
use App\Security\RolesService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class UserController
 *
 * @Route(
 *     path="/user",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @SWG\Tag(name="User Management")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method UserResource getResource()
 */
class UserController extends Controller
{
    // Traits for REST actions
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\FindOneAction;
    use Actions\Admin\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;
    use Methods\DeleteMethod;

    /**
     * @var array<string, string>
     */
    protected static $dtoClasses = [
        Controller::METHOD_CREATE => UserCreate::class,
        Controller::METHOD_UPDATE => UserUpdate::class,
        Controller::METHOD_PATCH => UserPatch::class,
    ];

    /**
     * UserController constructor.
     *
     * @param UserResource $resource
     */
    public function __construct(UserResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @Route(
     *      "/{requestUser}",
     *      requirements={
     *          "requestUser" = "%app.uuid_regex%",
     *      },
     *      methods={"DELETE"},
     *  )
     *
     * @ParamConverter(
     *     "requestUser",
     *     class="App\Resource\UserResource"
     *  )
     *
     * @Security("is_granted('ROLE_ROOT')")
     *
     * @RestApiDoc()
     *
     * @param Request $request
     * @param User    $requestUser
     * @param User    $loggedInUser
     *
     * @return Response
     *
     * @throws Throwable
     */
    public function deleteAction(Request $request, User $requestUser, User $loggedInUser): Response
    {
        if ($loggedInUser === $requestUser) {
            throw new HttpException(400, 'You cannot remove yourself...');
        }

        return $this->deleteMethod($request, $requestUser->getId());
    }

    /**
     * Endpoint action to fetch specified user roles.
     *
     * @Route(
     *      "/{requestUser}/roles",
     *      requirements={
     *          "requestUser" = "%app.uuid_regex%",
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
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(type="string"),
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
     *      description="Access denied",
     *      examples={
     *          "Access denied": "{code: 403, message: 'Access denied'}",
     *      },
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @SWG\Tag(name="User Management")
     *
     * @param User         $requestUser
     * @param RolesService $roles
     *
     * @return JsonResponse
     */
    public function getUserRolesAction(User $requestUser, RolesService $roles): JsonResponse
    {
        return new JsonResponse($roles->getInheritedRoles($requestUser->getRoles()));
    }

    /**
     * Endpoint action to fetch specified user user groups.
     *
     * @Route(
     *      "/{requestUser}/groups",
     *      requirements={
     *          "requestUser" = "%app.uuid_regex%",
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
     *      description="Access denied",
     *      examples={
     *          "Access denied": "{code: 403, message: 'Access denied'}",
     *      },
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @SWG\Tag(name="User Management")
     *
     * @param User                $requestUser
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     */
    public function getUserGroupsAction(User $requestUser, SerializerInterface $serializer): JsonResponse
    {
        return $this->getUserGroupResponse($requestUser, $serializer);
    }

    /**
     * Endpoint action to attach specified user group to specified user.
     *
     * @Route(
     *      "/{user}/group/{userGroup}",
     *      requirements={
     *          "user" = "%app.uuid_regex%",
     *          "userGroup" = "%app.uuid_regex%",
     *      },
     *      methods={"POST"},
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
     *      description="User groups (user already belongs to this group)",
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
     *      response=201,
     *      description="User groups (user added to this group)",
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
     *      description="Access denied",
     *      examples={
     *          "Access denied": "{code: 403, message: 'Access denied'}",
     *      },
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @SWG\Tag(name="User Management")
     *
     * @param User                $user
     * @param UserGroup           $userGroup
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function attachUserGroupAction(
        User $user,
        UserGroup $userGroup,
        SerializerInterface $serializer
    ): JsonResponse {
        $status = $user->getUserGroups()->contains($userGroup) ? 200 : 201;

        $this->getResource()->save($user->addUserGroup($userGroup));

        return $this->getUserGroupResponse($user, $serializer, $status);
    }

    /**
     * Endpoint action to detach specified user group from specified user.
     *
     * @Route(
     *      "/{user}/group/{userGroup}",
     *      requirements={
     *          "user" = "%app.uuid_regex%",
     *          "userGroup" = "%app.uuid_regex%",
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
     * @SWG\Tag(name="User Management")
     *
     * @param User                $user
     * @param UserGroup           $userGroup
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function detachUserGroupAction(
        User $user,
        UserGroup $userGroup,
        SerializerInterface $serializer
    ): JsonResponse {
        $this->getResource()->save($user->removeUserGroup($userGroup));

        return $this->getUserGroupResponse($user, $serializer);
    }

    /**
     * Helper method to create UserGroup response.
     *
     * @param User                $user
     * @param SerializerInterface $serializer
     * @param int|null            $status
     *
     * @return JsonResponse
     */
    private function getUserGroupResponse(
        User $user,
        SerializerInterface $serializer,
        ?int $status = null
    ): JsonResponse {
        $status ??= 200;

        $groups = [
            'groups' => [
                'UserGroup',
                'UserGroup.role',
            ],
        ];

        return new JsonResponse(
            $serializer->serialize($user->getUserGroups()->getValues(), 'json', $groups),
            $status,
            [],
            true
        );
    }
}
