<?php
declare(strict_types=1);
/**
 * /src/App/Controller/DefaultController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use Psr\Log\LoggerInterface as Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *service="app.controller.default",
 *
 * @Route(path="/")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DefaultController
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor of the class.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Default application response when requested root.
     *
     * @Route("")
     *
     * @Method("GET");
     *
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        $this->logger->info('test');

        return new Response('Hello world', Response::HTTP_OK);
    }
}
