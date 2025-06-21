<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Profile/IndexControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Profile;

use App\Controller\v1\Profile\IndexController;
use App\Entity\User;
use App\Security\RolesService;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @package App\Tests\Integration\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IndexControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `__invoke(User $loggedInUser)` method calls expected service methods')]
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $user = new User();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with(
                $user,
                'json',
                [
                    'groups' => 'set.UserProfile',
                ],
            )
            ->willReturn('{"roles": ["foo", "bar"]}');

        $rolesService
            ->expects($this->once())
            ->method('getInheritedRoles')
            ->with(['foo', 'bar'])
            ->willReturn(['foo', 'bar']);

        new IndexController($serializer, $rolesService)($user);
    }
}
