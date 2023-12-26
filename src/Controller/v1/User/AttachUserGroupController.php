<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/User/AttachUserGroupController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\User;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Enum\Role;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class AttachUserGroupController
 *
 * @package App\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class AttachUserGroupController
{
    public function __construct(
        private readonly UserResource $userResource,
        private readonly UserGroupResource $userGroupResource,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Endpoint action to attach specified user group to specified user.
     *
     * @throws Throwable
     */
    #[Route(
        path: '/v1/user/{user}/group/{userGroup}',
        requirements: [
            'user' => Requirement::UUID_V1,
            'userGroup' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_POST],
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
        description: 'User groups (user already belongs to this group)',
        content: new JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: UserGroup::class, groups: ['UserGroup', 'UserGroup.role']),
            ),
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'User groups (user added to this group)',
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
        $status = $user->getUserGroups()->contains($userGroup) ? Response::HTTP_OK : Response::HTTP_CREATED;

        $this->userResource->save($user->addUserGroup($userGroup), false);
        $this->userGroupResource->save($userGroup, true, true);

        $groups = [
            'groups' => [
                UserGroup::SET_USER_GROUP_BASIC,
            ],
        ];

        return new JsonResponse(
            $this->serializer->serialize($user->getUserGroups()->getValues(), 'json', $groups),
            $status,
            json: true
        );
    }
}
