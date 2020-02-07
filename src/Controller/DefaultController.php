<?php
declare(strict_types = 1);
/**
 * /src/Controller/DefaultController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller;

use App\Rest\ResponseHandler;
use App\Service\Version;
use App\Utils\HealthzService;
use Doctrine\Common\Collections\ArrayCollection;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class DefaultController
 *
 * @Route(
 *     path="/",
 *  )
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DefaultController
{
    /**
     * Default application response when requested root.
     *
     * @Route(
     *     path="",
     *  )
     *
     * @return Response
     *
     * @throws Throwable
     */
    public function index(): Response
    {
        return new Response('', Response::HTTP_OK);
    }

    /**
     * Route for application health check. This action will make some simple tasks to ensure that application is up
     * and running like expected.
     *
     * @link https://kubernetes.io/docs/tasks/configure-pod-container/configure-liveness-readiness-probes/
     *
     * @Route(
     *     path="/healthz",
     *     methods={"GET"}
     *  )
     *
     * @SWG\Response(
     *      response=200,
     *      description="success",
     *      @SWG\Schema(
     *          type="object",
     *          example={"timestamp": "2018-01-01T13:08:05+00:00"},
     *          @SWG\Property(property="timestamp", type="string"),
     *      ),
     *  )
     *
     * @param Request         $request
     * @param ResponseHandler $responseHandler
     * @param HealthzService  $healthzService
     *
     * @return Response
     *
     * @throws Throwable
     */
    public function healthz(
        Request $request,
        ResponseHandler $responseHandler,
        HealthzService $healthzService
    ): Response {
        return $responseHandler->createResponse(
            $request,
            $healthzService->check(),
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

    /**
     * Route for get API version.
     *
     * @Route(
     *     path="/version",
     *     methods={"GET"}
     *  )
     *
     * @SWG\Response(
     *      response=200,
     *      description="success",
     *      @SWG\Schema(
     *          type="object",
     *          example={"version": "1.2.3"},
     *          @SWG\Property(property="version", type="string", description="Version number"),
     *      ),
     *  )
     *
     * @param Version $version
     *
     * @return JsonResponse
     */
    public function version(Version $version): JsonResponse
    {
        $data = [
            'version' => $version->get(),
        ];

        return new JsonResponse($data);
    }
}
