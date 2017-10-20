<?php
declare(strict_types = 1);
/**
 * /src/Controller/DefaultController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Rest\ResponseHandler;
use App\Utils\HealthzService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
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
     * Default application response when requested root.
     *
     * @Route("")
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function indexAction(): Response
    {
        return new Response('', Response::HTTP_OK);
    }

    /**
     * Route for application health check. This action will make some simple tasks to ensure that application is up
     * and running like expected.
     *
     * @link https://kubernetes.io/docs/tasks/configure-pod-container/configure-liveness-readiness-probes/
     *
     * @Route("/healthz")
     *
     * @Method("GET")
     *
     * @param Request         $request
     * @param ResponseHandler $responseHandler
     * @param HealthzService  $healthzService
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function healthzAction(
        Request $request,
        ResponseHandler $responseHandler,
        HealthzService $healthzService
    ): Response {
        return $responseHandler->createResponse(
            $request,
            $healthzService->check(),
            null,
            null,
            [
                'groups' => [
                    'Healthz.timestamp'
                ]
            ]
        );
    }
}
