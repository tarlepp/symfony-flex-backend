<?php
declare(strict_types = 1);
/**
 * /src/Controller/UserController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Annotation\RestApiDoc;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\Type\Rest\User\UserCreateType;
use App\Form\Type\Rest\User\UserPatchType;
use App\Form\Type\Rest\User\UserUpdateType;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use App\Rest\Traits\Methods;
use App\Security\RolesService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */
/**
 * Class UserController
 *
 * @Route(path="/user")
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
    /**
     * Method + Form type class names (key + value)
     *
     * @var string[]
     */
    protected static $formTypes = [
        self::METHOD_PATCH  => UserPatchType::class,
        self::METHOD_CREATE => UserCreateType::class,
        self::METHOD_UPDATE => UserUpdateType::class,
    ];

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
     * UserController constructor.
     *
     * @param UserResource    $resource
     * @param ResponseHandler $responseHandler
     */
    public function __construct(UserResource $resource, ResponseHandler $responseHandler)
    {
        $this->init($resource, $responseHandler);
    }

    /**
     * @Route(
     *      "/{id}",
     *      requirements={
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
     *      }
     *  )
     *
     * @ParamConverter(
     *     "requestUser",
     *     class="App:User"
     *  )
     *
     * @Method({"DELETE"})
     *
     * @Security("has_role('ROLE_ROOT')")
     *
     * @RestApiDoc()
     *
     * @param Request               $request
     * @param User                  $requestUser
     * @param TokenStorageInterface $tokenStorage
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function deleteAction(Request $request, User $requestUser, TokenStorageInterface $tokenStorage): Response
    {
        /** @noinspection NullPointerExceptionInspection */
        $currentUser = $tokenStorage->getToken()->getUser();

        if ($currentUser === $requestUser) {
            throw new HttpException(400, 'You cannot remove yourself...');
        }

        return $this->deleteMethod($request, $requestUser->getId());
    }

    /**
     * Endpoint action to fetch specified user roles.
     *
     * @Route(
     *      "/{id}/roles",
     *      requirements={
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
     *      }
     *  )
     *
     * @ParamConverter(
     *     "requestUser",
     *     class="App:User"
     *  )
     *
     * @Method({"GET"})
     *
     * @Security("is_granted('IS_USER_HIMSELF', requestUser) or has_role('ROLE_ROOT')")
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
     *      "/{id}/groups",
     *      requirements={
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
     *      }
     *  )
     *
     * @ParamConverter(
     *     "requestUser",
     *     class="App:User"
     *  )
     *
     * @Method({"GET"})
     *
     * @Security("is_granted('IS_USER_HIMSELF', requestUser) or has_role('ROLE_ROOT')")
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
     *          @Model(
     *              type=App\Entity\UserGroup::class,
     *              groups={"UserGroup", "UserGroup.role"},
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
     *      "/{userId}/group/{userGroupId}",
     *      requirements={
     *          "userId" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *          "userGroupId" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *      }
     *  )
     *
     * @ParamConverter(
     *      "user",
     *      class="App:User",
     *      options={
     *          "id" = "userId",
     *      },
     *  )
     * @ParamConverter(
     *      "userGroup",
     *      class="App:UserGroup",
     *      options={
     *          "id" = "userGroupId",
     *      },
     *  )
     *
     * @Method({"POST"})
     *
     * @Security("has_role('ROLE_ROOT')")
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
     *          @Model(
     *              type=App\Entity\UserGroup::class,
     *              groups={"UserGroup", "UserGroup.role"},
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
     *      "/{userId}/group/{userGroupId}",
     *      requirements={
     *          "userId" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *          "userGroupId" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *      }
     *  )
     *
     * @ParamConverter(
     *      "user",
     *      class="App:User",
     *      options={
     *          "id" = "userId",
     *      },
     *  )
     * @ParamConverter(
     *      "userGroup",
     *      class="App:UserGroup",
     *      options={
     *          "id" = "userGroupId",
     *      },
     *  )
     *
     * @Method({"DELETE"})
     *
     * @Security("has_role('ROLE_ROOT')")
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
     *          @Model(
     *              type=App\Entity\UserGroup::class,
     *              groups={"UserGroup", "UserGroup.role"},
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
    private function getUserGroupResponse(User $user, SerializerInterface $serializer, int $status = null): JsonResponse
    {
        $status = $status ?? 200;

        static $groups = [
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
