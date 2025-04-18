<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/User/DetachUserGroupController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\User;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Enum\Role;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @package App\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class DetachUserGroupController
{
    public function __construct(
        private readonly UserResource $userResource,
        private readonly UserGroupResource $userGroupResource,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Endpoint action to detach specified user group from specified user.
     *
     * @throws Throwable
     */
    #[Route(
        path: '/v1/user/{user}/group/{userGroup}',
        requirements: [
            'user' => Requirement::UUID_V1,
            'userGroup' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(Role::ROOT->value)]
    #[OA\Tag(name: 'User Management')]
    #[OA\Parameter(
        name: 'Authorization',
        description: 'Authorization header',
        in: 'header',
        required: true,
        example: 'Bearer {token}',
        allowReserved: true,
    )]
    #[OA\Parameter(name: 'user', description: 'User GUID', in: 'path', required: true)]
    #[OA\Parameter(name: 'userGroup', description: 'User Group GUID', in: 'path', required: true)]
    #[OA\Response(
        response: 200,
        description: 'User groups',
        content: new JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: UserGroup::class, groups: ['UserGroup', 'UserGroup.role']),
            ),
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid token',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', type: 'integer'),
                new Property(property: 'message', type: 'string'),
            ],
            type: 'object',
            example: [
                'Token not found' => "{code: 401, message: 'JWT Token not found'}",
                'Expired token' => "{code: 401, message: 'Expired JWT Token'}",
            ],
        ),
    )]
    #[OA\Response(
        response: 403,
        description: 'Access denied',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', type: 'integer'),
                new Property(property: 'message', type: 'string'),
            ],
            type: 'object',
            example: [
                'Access denied' => "{code: 403, message: 'Access denied'}",
            ],
        ),
    )]
    public function __invoke(User $user, UserGroup $userGroup): JsonResponse
    {
        $this->userResource->save($user->removeUserGroup($userGroup), false);
        $this->userGroupResource->save($userGroup, true, true);

        $groups = [
            'groups' => [
                UserGroup::SET_USER_GROUP_BASIC,
            ],
        ];

        return new JsonResponse(
            $this->serializer->serialize($user->getUserGroups()->getValues(), 'json', $groups),
            json: true
        );
    }
}
