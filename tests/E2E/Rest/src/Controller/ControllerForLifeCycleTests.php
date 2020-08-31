<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/src/Controller/ControllerForLifeCycleTests.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\src\Controller;

use App\Rest\Controller;
use App\Rest\Traits\Methods;
use App\Tests\E2E\Rest\src\Resource\ResourceForLifeCycleTests;
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
 * @package App\Tests\E2E\Rest\src\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ControllerForLifeCycleTests extends Controller
{
    // Traits

    use Methods\FindOneMethod;

    /**
     * ControllerForLifeCycleTests constructor.
     */
    public function __construct(ResourceForLifeCycleTests $resource)
    {
        $this->resource = $resource;
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
     * @throws Throwable
     */
    public function findOneAction(Request $request, string $role): Response
    {
        return $this->findOneMethod($request, $role);
    }
}
