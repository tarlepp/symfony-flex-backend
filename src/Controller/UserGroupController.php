<?php
declare(strict_types = 1);
/**
 * /src/Controller/UserGroupController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\Type\Rest\UserGroup\UserGroupType;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class UserGroupController
 *
 * @Route(
 *     path="/user_group",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @SWG\Tag(name="UserGroup Management")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method UserGroupResource getResource()
 */
class UserGroupController extends Controller
{
    // Traits for REST actions
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\FindOneAction;
    use Actions\Admin\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\DeleteAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;
    /**
     * Method + Form type class names (key + value)
     *
     * @var string[]
     */
    protected static $formTypes = [
        Controller::METHOD_PATCH => UserGroupType::class,
        Controller::METHOD_CREATE => UserGroupType::class,
        Controller::METHOD_UPDATE => UserGroupType::class,
    ];

    /**
     * UserGroupController constructor.
     *
     * @param UserGroupResource $resource
     */
    public function __construct(UserGroupResource $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Endpoint action to list specified user group users.
     *
     * @Route(
     *      "/{userGroup}/users",
     *      requirements={
     *          "userGroup" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *      },
     *      methods={"GET"},
     *  )
     *
     * @ParamConverter(
     *      "userGroup",
     *      class="App\Resource\UserGroupResource",
     *  )
     *
     * @Security("is_granted('ROLE_ADMIN')")
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
     *      description="User group users",
     *      @SWG\Schema(
     *          ref=@Model(
     *              type=User::class,
     *              groups={"User", "User.userGroups", "User.roles", "UserGroup", "UserGroup.role"},
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Response(
     *      response=404,
     *      description="User Group not found",
     *  )
     * @SWG\Tag(name="UserGroup Management")
     *
     * @param Request      $request
     * @param UserResource $userResource
     * @param UserGroup    $userGroup
     *
     * @return Response
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getUserGroupUsersAction(
        Request $request,
        UserResource $userResource,
        UserGroup $userGroup
    ): Response {
        return $this
            ->getResponseHandler()
            ->createResponse($request, $userResource->getUsersForGroup($userGroup), $userResource);
    }

    /**
     * Endpoint action to attach specified user to specified user group.
     *
     * @Route(
     *      "/{userGroup}/user/{user}",
     *      requirements={
     *          "userGroup" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *          "user" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *      },
     *      methods={"POST"},
     *  )
     *
     * @ParamConverter(
     *      "userGroup",
     *      class="App\Resource\UserGroupResource",
     *  )
     * @ParamConverter(
     *      "user",
     *      class="App\Resource\UserResource",
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
     *      name="userGroupId",
     *      in="path",
     *      required=true,
     *      description="User Group GUID",
     *      default="User Group GUID",
     *  )
     * @SWG\Parameter(
     *      type="string",
     *      name="userId",
     *      in="path",
     *      required=true,
     *      description="User GUID",
     *      default="User GUID",
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="List of user group users - specified user already exists on this group",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(
     *              ref=@Model(
     *                  type=App\Entity\User::class,
     *                  groups={"User"},
     *              ),
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=201,
     *      description="List of user group users - specified user has been attached to this group",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(
     *              ref=@Model(
     *                  type=App\Entity\User::class,
     *                  groups={"User"},
     *              ),
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Response(
     *      response=403,
     *      description="Access denied",
     *  )
     * @SWG\Tag(name="UserGroup Management")
     *
     * @param UserGroup           $userGroup
     * @param User                $user
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function attachUserAction(
        UserGroup $userGroup,
        User $user,
        SerializerInterface $serializer
    ): JsonResponse {
        $status = $userGroup->getUsers()->contains($user) ? 200 : 201;

        $this->getResource()->save($userGroup->addUser($user));

        return $this->getUserResponse($userGroup, $serializer, $status);
    }

    /**
     * Endpoint action to detach specified user from specified user group.
     *
     * @Route(
     *      "/{userGroup}/user/{user}",
     *      requirements={
     *          "userGroupId" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *          "userId" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *      },
     *      methods={"DELETE"},
     *  )
     *
     * @ParamConverter(
     *      "userGroup",
     *      class="App\Resource\UserGroupResource",
     *  )
     * @ParamConverter(
     *      "user",
     *      class="App\Resource\UserResource",
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
     *      name="userGroupId",
     *      in="path",
     *      required=true,
     *      description="User Group GUID",
     *      default="User Group GUID",
     *  )
     * @SWG\Parameter(
     *      type="string",
     *      name="userId",
     *      in="path",
     *      required=true,
     *      description="User GUID",
     *      default="User GUID",
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="Users",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(
     *              ref=@Model(
     *                  type=App\Entity\User::class,
     *                  groups={"User"},
     *              ),
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Response(
     *      response=403,
     *      description="Access denied",
     *  )
     * @SWG\Tag(name="UserGroup Management")
     *
     * @param UserGroup           $userGroup
     * @param User                $user
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function detachUserAction(
        UserGroup $userGroup,
        User $user,
        SerializerInterface $serializer
    ): JsonResponse {
        $this->getResource()->save($userGroup->removeUser($user));

        return $this->getUserResponse($userGroup, $serializer);
    }

    /**
     * Helper method to create User response.
     *
     * @param UserGroup           $userGroup
     * @param SerializerInterface $serializer
     * @param int|null            $status
     *
     * @return JsonResponse
     */
    private function getUserResponse(
        UserGroup $userGroup,
        SerializerInterface $serializer,
        ?int $status = null
    ): JsonResponse {
        $status = $status ?? 200;

        static $groups = [
            'groups' => [
                'User',
            ],
        ];

        return new JsonResponse(
            $serializer->serialize($userGroup->getUsers()->getValues(), 'json', $groups),
            $status,
            [],
            true
        );
    }
}
