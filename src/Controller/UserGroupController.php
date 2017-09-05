<?php
declare(strict_types=1);
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
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */
/**
 * Class UserGroupController
 *
 * @Route(path="/user_group")
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
    /**
     * Method + Form type class names (key + value)
     *
     * @var string[]
     */
    protected static $formTypes = [
        self::METHOD_PATCH  => UserGroupType::class,
        self::METHOD_CREATE => UserGroupType::class,
        self::METHOD_UPDATE => UserGroupType::class,
    ];

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
     * UserGroupController constructor.
     *
     * @param UserGroupResource $userGroupResource
     * @param ResponseHandler   $responseHandler
     */
    public function __construct(UserGroupResource $userGroupResource, ResponseHandler $responseHandler)
    {
        $this->init($userGroupResource, $responseHandler);
    }

    /**
     * Endpoint action to list specified user group users.
     *
     * @Route(
     *      "/{id}/users",
     *      requirements={
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
     *      }
     *  )
     *
     * @ParamConverter(
     *     "user",
     *     class="App:UserGroup"
     *  )
     *
     * @Method({"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
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
     *          @Model(
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
        // Manually change used resource class, so that serializer groups are correct ones
        $this->getResponseHandler()->setResource($userResource);

        return $this->getResponseHandler()->createResponse($request, $userResource->getUsersForGroup($userGroup));
    }
}
