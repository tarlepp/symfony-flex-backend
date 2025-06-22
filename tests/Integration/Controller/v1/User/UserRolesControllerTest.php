<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/User/UserRolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\User;

use App\Controller\v1\User\UserRolesController;
use App\Entity\User;
use App\Security\RolesService;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserRolesControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `__invoke(User $requestUser)` method calls expected service methods')]
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

        new UserRolesController($rolesService)->__invoke($user);
    }
}
