<?php
declare(strict_types=1);
/**
 * /src/Controller/RoleController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Entity\Role;
use App\Resource\RoleResource;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use App\Rest\Traits\Methods;
use App\Security\Roles;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */

/**
 * Class RoleController
 *
 * @Route(path="/role")
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
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
     * @param RoleResource    $resource
     * @param ResponseHandler $responseHandler
     */
    public function __construct(RoleResource $resource, ResponseHandler $responseHandler)
    {
        $this->init($resource, $responseHandler);
    }

    /**
     * Endpoint action to fetch single Role entity from database and show it as a JSON response. Note that we cannot
     * use generic 'findOneAction' REST trait in this case because Role entity ID's are not V4 UUID strings.
     *
     * @Route(
     *      "/{role}",
     *      requirements={
     *          "role" = "^ROLE_\w+$"
     *      }
     *  )
     *
     * @Method({"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param string  $role
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
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
     *          "role" = "^ROLE_\w+$"
     *      }
     *  )
     *
     * @ParamConverter(
     *     "role",
     *     class="App:Role"
     * )
     *
     * @Method({"GET"})
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @param Roles $roles
     * @param Role  $role
     *
     * @return JsonResponse
     */
    public function getInheritedRolesAction(Roles $roles, Role $role): JsonResponse
    {
        return new JsonResponse($roles->getInheritedRoles([$role->getId()]));
    }
}
