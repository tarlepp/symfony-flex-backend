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
use OpenApi\Annotations as OA;
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
     * @OA\RequestBody(
     *      request="body",
     *      description="Credentials object",
     *      required=true,
     *      @OA\Schema(
     *          example={"username": "username", "password": "password"},
     *      )
     *  )
     * @OA\Response(
     *      response=200,
     *      description="JSON Web Token for user",
     *      @OA\Schema(
     *          type="object",
     *          example={"token": "_json_web_token_"},
     *          @OA\Property(property="token", type="string", description="Json Web Token"),
     *      ),
     *  )
     * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *      @OA\Schema(
     *          type="object",
     *          example={"code": 400, "message": "Bad Request"},
     *          @OA\Property(property="code", type="integer", description="Error code"),
     *          @OA\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @OA\Response(
     *      response=401,
     *      description="Unauthorized",
     *      @OA\Schema(
     *          type="object",
     *          example={"code": 401, "message": "Bad credentials"},
     *          @OA\Property(property="code", type="integer", description="Error code"),
     *          @OA\Property(property="message", type="string", description="Error description"),
     *      ),
     *  )
     * @OA\Tag(name="Authentication")
     *
     * @throws HttpException
     * @throws JsonException
     */
    #[Route(
        path: '/v1/auth/get_token',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(): void
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
