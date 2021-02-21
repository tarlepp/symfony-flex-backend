<?php
declare(strict_types = 1);
/**
 * /src/Controller/IndexController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class IndexController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IndexController
{
    /**
     * Default application response when requested root.
     *
     * @throws Throwable
     */
    #[Route(
        path: '/',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(): Response
    {
        return new Response();
    }
}
