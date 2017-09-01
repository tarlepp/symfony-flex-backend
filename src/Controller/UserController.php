<?php
declare(strict_types=1);
/**
 * /src/Controller/UserController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Annotation\RestApiDoc;
use App\Entity\User;
use App\Form\Type\Rest\User\UserCreateType;
use App\Form\Type\Rest\User\UserPatchType;
use App\Form\Type\Rest\User\UserUpdateType;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use App\Rest\Traits\Methods;
use App\Security\Roles;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\User\UserInterface;

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
     *     "user",
     *     class="App:User"
     *  )
     *
     * @Method({"DELETE"})
     *
     * @Security("has_role('ROLE_ROOT')")
     *
     * @RestApiDoc()
     *
     * @param Request            $request
     * @param User               $user
     * @param User|UserInterface $currentUser
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function deleteAction(Request $request, User $user, UserInterface $currentUser): Response
    {
        if ($currentUser === $user) {
            throw new HttpException(400, 'You cannot remove yourself...');
        }

        return $this->deleteMethod($request, $user->getId());
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
     * @SWG\Tag(name="User Management")
     *
     * @param User  $requestUser
     * @param Roles $roles
     *
     * @return JsonResponse
     */
    public function getUserRolesAction(User $requestUser, Roles $roles): JsonResponse
    {
        return new JsonResponse($roles->getInheritedRoles($requestUser->getRoles()));
    }
}
