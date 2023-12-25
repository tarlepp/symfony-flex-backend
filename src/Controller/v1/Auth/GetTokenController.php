<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/Auth/GetTokenController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\Auth;

use App\Utils\JSON;
use JsonException;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use function sprintf;

/**
 * Class GetTokenController
 *
 * @package App\Controller\v1\Auth
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class GetTokenController
{
    /**
     * Endpoint action to get user Json Web Token (JWT) for authentication.
     *
     * @throws HttpException
     * @throws JsonException
     */
    #[Route(
        path: '/v1/auth/get_token',
        methods: [Request::METHOD_POST],
    )]
    #[OA\RequestBody(
        request: 'body',
        description: 'Credentials object',
        required: true,
        content: new JsonContent(
            properties: [
                new Property(property: 'username', type: 'string'),
                new Property(property: 'password', type: 'string'),
            ],
            type: 'object',
            example: [
                'username' => 'username',
                'password' => 'password',
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'JSON Web Token for user',
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'token',
                    description: 'Json Web Token',
                    type: 'string',
                ),
            ],
            type: 'object',
            example: [
                'token' => '_json_web_token_',
            ],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad Request',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', type: 'integer'),
                new Property(property: 'message', type: 'string'),
            ],
            type: 'object',
            example: [
                'code' => 400,
                'message' => 'Bad Request',
            ],
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', type: 'integer'),
                new Property(property: 'message', type: 'string'),
            ],
            type: 'object',
            example: [
                'code' => 401,
                'message' => 'Bad credentials',
            ],
        ),
    )]
    #[OA\Tag(name: 'Authentication')]
    public function __invoke(): never
    {
        $message = sprintf(
            'You need to send JSON body to obtain token eg. %s',
            JSON::encode([
                'username' => 'username',
                'password' => 'password',
            ]),
        );

        throw new HttpException(Response::HTTP_BAD_REQUEST, $message);
    }
}
