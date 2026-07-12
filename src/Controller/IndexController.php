<?php
declare(strict_types = 1);

/**
 * /src/Controller/IndexController.php
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class IndexController
{
    /**
     * Default application response when requested root.
     */
    #[Route(
        path: '/',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse();
    }
}
