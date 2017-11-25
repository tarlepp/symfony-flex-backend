<?php
declare(strict_types = 1);
/**
 * /src/Controller/AuthController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Utils\JSON;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AuthController
 *
 * @Route(
 *      path="/auth",
 *  )
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthController
{
    /**
     * Endpoint action to get user Json Web Token (JWT) for authentication.
     *
     * @Route("/getToken");
     *
     * @Method("POST")
     *
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      description="Credentials object",
     *      required=true,
     *      @SWG\Schema(
     *          example={"username": "username", "password": "password"}
     *      )
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="JSON Web Token for user",
     *  )
     * @SWG\Response(
     *      response=400,
     *      description="Invalid body content",
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Bad credentials",
     *  )
     * @SWG\Tag(name="Authentication")
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getTokenAction(): void
    {
        $message = \sprintf(
            'You need to send JSON body to obtain token eg. %s',
            JSON::encode(['username' => 'username', 'password' => 'password'])
        );

        throw new HttpException(400, $message);
    }
}
