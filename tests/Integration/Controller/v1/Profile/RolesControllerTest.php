<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Profile/RolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Profile;

use App\Controller\v1\Profile\RolesController;
use App\Entity\User;
use App\Security\RolesService;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class RolesControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `__invoke(User $loggedInUser)` method calls expected service methods')]
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $user = new User();

        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rolesService
            ->expects($this->once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn([]);

        new RolesController($rolesService)->__invoke($user);
    }
}
