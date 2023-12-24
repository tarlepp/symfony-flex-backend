<?php
declare(strict_types = 1);
/**
 * /src/Controller/VersionController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use App\Service\Version;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
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
     * Route to get API version.
     */
    #[Route(
        path: '/version',
        methods: [Request::METHOD_GET],
    )]
    #[OA\Get(
        operationId: 'version',
        responses: [
            new OA\Response(
                response: 200,
                description: 'success',
                content: new JsonContent(
                    properties: [
                        new Property(
                            property: 'version',
                            description: 'Version number of the API in semver format',
                            type: 'string',
                        ),
                    ],
                    type: 'object',
                    example: [
                        'version' => '1.2.3',
                    ],
                ),
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'version' => $this->version->get(),
        ]);
    }
}
