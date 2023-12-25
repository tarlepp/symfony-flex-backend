<?php
declare(strict_types = 1);
/**
 * /src/Controller/HealthzController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\ResponseHandler;
use App\Utils\HealthzService;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class HealthzController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
class HealthzController
{
    public function __construct(
        private readonly ResponseHandler $responseHandler,
        private readonly HealthzService $healthzService,
    ) {
    }

    /**
     * Route for application health check. This action will make some simple
     * tasks to ensure that application is up and running like expected.
     *
     * @see https://kubernetes.io/docs/tasks/configure-pod-container/configure-liveness-readiness-probes/
     *
     * @throws Throwable
     */
    #[Route(
        path: '/healthz',
        methods: [Request::METHOD_GET],
    )]
    #[OA\Get(
        operationId: 'healthz',
        responses: [
            new OA\Response(
                response: 200,
                description: 'success',
                content: new JsonContent(
                    properties: [
                        new Property(
                            property: 'timestamp',
                            description: 'Timestamp when health check was performed',
                            type: 'string',
                        ),
                    ],
                    type: 'object',
                    example: [
                        'timestamp' => '2018-01-01T13:08:05+00:00',
                    ],
                ),
            ),
        ],
    )]
    public function __invoke(Request $request): Response
    {
        return $this->responseHandler->createResponse(
            $request,
            $this->healthzService->check(),
            format: ResponseHandlerInterface::FORMAT_JSON,
            context: [
                'groups' => [
                    'Healthz.timestamp',
                ],
            ],
        );
    }
}
