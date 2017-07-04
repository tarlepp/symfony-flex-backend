<?php
declare(strict_types=1);
/**
 * /src/Controller/UserGroupController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Entity\UserGroup;
use App\Form\Rest\UserGroup\UserGroupType;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserGroupController
 *
 * @Route(path="/user_group")
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @package App\Controller
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
    ): Response
    {
        // Manually change used resource class, so that serializer groups are correct ones
        $this->getResponseHandler()->setResource($userResource);

        return $this->getResponseHandler()->createResponse($request, $userResource->getUsersForGroup($userGroup));
    }
}
