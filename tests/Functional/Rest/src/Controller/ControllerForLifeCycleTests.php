<?php
declare(strict_types=1);
/**
 * /tests/Functional/Rest/src/Controller/ControllerForLifeCycleTests.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Rest\src\Controller;

use App\Annotation\RestApiDoc;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Methods;
use App\Tests\Functional\Rest\src\Resource\ResourceForLifeCycleTests;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class ControllerForLifeCycleTests
 *
 * @Route(
 *     path="/test_lifecycle_behaviour",
 *  )
 *
 * @RestApiDoc(disabled=true)
 *
 * @package App\Tests\Functional\Rest\src\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ControllerForLifeCycleTests extends Controller
{
    // Traits
    use Methods\FindOneMethod;

    /**
     * ControllerForLifeCycleTests constructor.
     *
     * @param ResourceForLifeCycleTests $resource
     * @param ResponseHandler           $responseHandler
     */
    public function __construct(ResourceForLifeCycleTests $resource, ResponseHandler $responseHandler)
    {
        $this->init($resource, $responseHandler);
    }

    /**
     * @Route(
     *      "/{role}",
     *      requirements={
     *          "role" = "^ROLE_\w+$"
     *      },
     *      methods={"GET"}
     *  )
     *
     * @param Request $request
     * @param string  $role
     *
     * @return Response
     *
     * @throws LogicException
     * @throws Throwable
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function findOneAction(Request $request, string $role): Response
    {
        return $this->findOneMethod($request, $role);
    }
}
