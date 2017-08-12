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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
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
}
