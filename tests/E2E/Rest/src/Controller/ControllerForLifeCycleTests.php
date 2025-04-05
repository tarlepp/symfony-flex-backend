<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/src/Controller/ControllerForLifeCycleTests.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest\src\Controller;

use App\Enum\Role;
use App\Rest\Controller;
use App\Rest\Traits\Methods;
use App\Tests\E2E\Rest\src\Resource\ResourceForLifeCycleTests;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;
use Throwable;

/**
 * @package App\Tests\E2E\Rest\src\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[Route(
    path: '/test_lifecycle_behaviour',
)]
#[AsController]
#[AutoconfigureTag('app.rest.controller')]
final class ControllerForLifeCycleTests extends Controller
{
    // Traits
    use Methods\FindOneMethod;

    public function __construct(
        ResourceForLifeCycleTests $resource,
    ) {
        parent::__construct($resource);
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/{role}',
        requirements: [
            'role' => new EnumRequirement(Role::class),
        ],
        methods: [Request::METHOD_GET],
    )]
    public function findOneAction(Request $request, string $role): Response
    {
        return $this->findOneMethod($request, $role);
    }
}
