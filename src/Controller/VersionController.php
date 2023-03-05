<?php
declare(strict_types = 1);
/**
 * /src/Controller/VersionController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use App\Service\Version;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class VersionController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class VersionController
{
    public function __construct(
        private readonly Version $version,
    ) {
    }

    /**
     * Route for get API version.
     *
     * @OA\Get(
     *      operationId="version",
     *      responses={
     *          @OA\Response(
     *               response=200,
     *               description="success",
     *               @OA\Schema(
     *                   type="object",
     *                   example={"version": "1.2.3"},
     *                   @OA\Property(property="version", type="string", description="Version number"),
     *               ),
     *           ),
     *      },
     *  )
     */
    #[Route(
        path: '/version',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'version' => $this->version->get(),
        ]);
    }
}
