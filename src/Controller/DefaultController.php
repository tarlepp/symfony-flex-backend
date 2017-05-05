<?php
declare(strict_types=1);
/**
 * /src/App/Controller/DefaultController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DefaultController
{
    /**
     * Default application response when requested root.
     *
     * @Route("/")
     *
     * @Method("GET");
     *
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return new Response('Hello world', Response::HTTP_OK);
    }
}
