<?php
declare(strict_types = 1);
/**
 * /src/Controller/HealthzController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use App\Rest\ResponseHandler;
use App\Utils\HealthzService;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class HealthzController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HealthzController
{
    private ResponseHandler $responseHandler;
    private HealthzService $healthzService;

    /**
     * HealthzController constructor.
     */
    public function __construct(ResponseHandler $responseHandler, HealthzService $healthzService)
    {
        $this->responseHandler = $responseHandler;
        $this->healthzService = $healthzService;
    }

    /**
     * Route for application health check. This action will make some simple
     * tasks to ensure that application is up and running like expected.
     *
     * @see https://kubernetes.io/docs/tasks/configure-pod-container/configure-liveness-readiness-probes/
     *
     * @Route(
     *     path="/healthz",
     *     methods={"GET"}
     *  )
     *
     * @OA\Response(
     *      response=200,
     *      description="success",
     *      @OA\Schema(
     *          type="object",
     *          example={"timestamp": "2018-01-01T13:08:05+00:00"},
     *          @OA\Property(property="timestamp", type="string"),
     *      ),
     *  )
     *
     * @throws Throwable
     */
    public function __invoke(Request $request): Response
    {
        return $this->responseHandler->createResponse(
            $request,
            $this->healthzService->check(),
            null,
            200,
            ResponseHandler::FORMAT_JSON,
            [
                'groups' => [
                    'Healthz.timestamp',
                ],
            ]
        );
    }
}
