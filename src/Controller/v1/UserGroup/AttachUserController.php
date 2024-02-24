<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/UserGroup/AttachUserController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\UserGroup;

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
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @package App\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class AttachUserController
{
    public function __construct(
        private readonly UserResource $userResource,
        private readonly UserGroupResource $userGroupResource,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Endpoint action to attach specified user to specified user group.
     *
     * @throws Throwable
     */
    #[Route(
        path: '/v1/user_group/{userGroup}/user/{user}',
        requirements: [
            'userGroup' => Requirement::UUID_V1,
            'user' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(Role::ROOT->value)]
    #[OA\Tag(name: 'UserGroup Management')]
    #[OA\Parameter(
        name: 'Authorization',
        description: 'Authorization header',
        in: 'header',
        required: true,
        example: 'Bearer {token}',
        allowReserved: true,
    )]
    #[OA\Parameter(name: 'userGroup', description: 'User Group GUID', in: 'path', required: true)]
    #[OA\Parameter(name: 'user', description: 'User GUID', in: 'path', required: true)]
    #[OA\Response(
        response: 200,
        description: 'List of user group users - specified user already exists on this group',
        content: new JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: User::class, groups: ['User']),
            ),
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'List of user group users - specified user has been attached to this group',
        content: new JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: User::class, groups: ['User']),
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
    public function __invoke(UserGroup $userGroup, User $user): JsonResponse
    {
        $status = $userGroup->getUsers()->contains($user) ? 200 : 201;

        $this->userGroupResource->save($userGroup->addUser($user), false);
        $this->userResource->save($user, true, true);

        $groups = [
            'groups' => [
                User::SET_USER_BASIC,
            ],
        ];

        return new JsonResponse(
            $this->serializer->serialize($userGroup->getUsers()->getValues(), 'json', $groups),
            $status,
            json: true,
        );
    }
}
