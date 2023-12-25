<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/User/UserGroupsController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\User;

use App\Entity\User;
use App\Entity\UserGroup;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class UserGroupsController
 *
 * @package App\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class UserGroupsController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Endpoint action to fetch specified user user groups.
     */
    #[Route(
        path: '/v1/user/{user}/groups',
        requirements: [
            'user' => Requirement::UUID_V1,
        ],
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(new Expression('is_granted("IS_USER_HIMSELF", object) or "ROLE_ROOT" in role_names'), 'user')]
    #[OA\Tag(name: 'User Management')]
    #[OA\SecurityScheme(
        securityScheme: 'bearerAuth',
        type: 'http',
        description: 'Authorization header',
        name: 'bearerAuth',
        in: 'header',
        bearerFormat: 'JWT',
        scheme: 'bearer',
    )]
    #[OA\Response(
        response: 200,
        description: 'User groups',
        content: new JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(
                    type: UserGroup::class,
                    groups: ['UserGroup', 'UserGroup.role'],
                ),
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
    public function __invoke(User $user): JsonResponse
    {
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
