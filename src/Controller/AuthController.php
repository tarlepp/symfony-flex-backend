<?php
declare(strict_types=1);
/**
 * /src/Controller/AuthController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

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
     * Action to get user's Json Web Token (JWT) for authentication.
     *
     * Note that the security layer will intercept this request.
     *
     * @Route("/getToken");
     * @Method("POST")
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function getTokenAction(): Response
    {
        return new Response('', 405);
    }
}
