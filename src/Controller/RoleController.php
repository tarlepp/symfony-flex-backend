<?php
declare(strict_types = 1);
/**
 * /src/Controller/RoleController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller;

use App\Annotation\RestApiDoc;
use App\Entity\Role;
use App\Resource\RoleResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use App\Rest\Traits\Methods;
use App\Security\RolesService;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class RoleController
 *
 * @Route(
 *     path="/role",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @SWG\Tag(name="Role Management")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method RoleResource getResource()
 */
class RoleController extends Controller
{
    // Traits for REST actions
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\IdsAction;
    use Methods\FindOneMethod;

    /**
     * RoleController constructor.
     *
     * @param RoleResource $resource
     */
    public function __construct(RoleResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @Route(
     *      path="/{role}",
     *      requirements={
     *          "role" = "^ROLE_\w+$",
     *      },
     *      methods={"GET"},
     *  )
     *
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @RestApiDoc()
     *
     * @param Request $request
     * @param string  $role
     *
     * @return Response
     *
     * @throws LogicException
     * @throws Throwable
     * @throws HttpException
     * @throws MethodNotAllowedHttpException
     */
    public function findOneAction(Request $request, string $role): Response
    {
        return $this->findOneMethod($request, $role);
    }

    /**
     * Endpoint action to return all inherited roles as an array for specified Role.
     *
     * @Route(
     *      "/{role}/inherited",
     *      requirements={
     *          "role" = "^ROLE_\w+$",
     *      },
     *     methods={"GET"}
     *  )
     *
     * @ParamConverter(
     *     "role",
     *     class="App\Resource\RoleResource"
     * )
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
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
     *      description="Inherited roles",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(
     *              type="string",
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
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     *
     * @RestApiDoc()
     *
     * @param Role         $role
     * @param RolesService $rolesService
     *
     * @return JsonResponse
     */
    public function getInheritedRolesAction(Role $role, RolesService $rolesService): JsonResponse
    {
        return new JsonResponse($rolesService->getInheritedRoles([$role->getId()]));
    }
}
