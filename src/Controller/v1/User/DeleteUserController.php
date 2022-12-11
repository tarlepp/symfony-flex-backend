<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/User/DeleteUserController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\User;

use App\Entity\User;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\Traits\Methods;
use App\Security\RolesService;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class DeleteUserController
 *
 * @OA\Tag(name="User Management")
 *
 * @package App\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DeleteUserController extends Controller
{
    use Methods\DeleteMethod;

    public function __construct(
        UserResource $resource,
    ) {
        parent::__construct($resource);
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/v1/user/{requestUser}',
        requirements: [
            'requestUser' => '%app.uuid_v1_regex%',
        ],
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(RolesService::ROLE_ROOT)]
    #[ParamConverter(
        data: 'requestUser',
        class: UserResource::class,
    )]
    public function __invoke(Request $request, User $requestUser, User $loggedInUser): Response
    {
        if ($loggedInUser === $requestUser) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'You cannot remove yourself...');
        }

        return $this->deleteMethod($request, $requestUser->getId());
    }
}
