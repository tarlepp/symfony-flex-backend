<?php
declare(strict_types = 1);
/**
 * /src/Controller/UserGroup/UsersController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\UserGroup;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use App\Rest\ResponseHandler;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class UsersController
 *
 * @package App\Controller\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UsersController
{
    private UserResource $userResource;
    private ResponseHandler $responseHandler;

    /**
     * UsersController constructor.
     */
    public function __construct(UserResource $userResource, ResponseHandler $responseHandler)
    {
        $this->userResource = $userResource;
        $this->responseHandler = $responseHandler;
    }

    /**
     * Endpoint action to list specified user group users.
     *
     * @Route(
     *      "/user_group/{userGroup}/users",
     *      requirements={
     *          "userGroup" = "%app.uuid_v1_regex%",
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
     * @SWG\Tag(name="UserGroup Management")
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
     *
     * @throws Throwable
     */
    public function __invoke(Request $request, UserGroup $userGroup): Response
    {
        return $this->responseHandler
            ->createResponse($request, $this->userResource->getUsersForGroup($userGroup), $this->userResource);
    }
}
