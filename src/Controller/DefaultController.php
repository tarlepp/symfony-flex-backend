<?php
declare(strict_types=1);
/**
 * /src/Controller/DefaultController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use Psr\Log\LoggerInterface as Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
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
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * Constructor of the class.
     *
     * @param Logger            $logger
     * @param \Twig_Environment $twig
     */
    public function __construct(Logger $logger, \Twig_Environment $twig)
    {
        $this->logger = $logger;
        $this->twig = $twig;
    }

    /**
     * Default application response when requested root.
     *
     * @Route("")
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     * @throws \Twig_Error_Syntax
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Loader
     */
    public function index(): Response
    {
        $this->logger->info('test');

        return new Response($this->twig->render('index.twig'), Response::HTTP_OK);
    }
}
