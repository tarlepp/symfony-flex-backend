<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/User/UserGroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\User;

use App\Controller\v1\User\UserGroupsController;
use App\Entity\User;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @package App\Tests\Integration\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupsControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `__invoke(User $requestUser)` method calls expected service methods')]
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $user = new User();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with(
                $user->getUserGroups()->getValues(),
                'json',
                [
                    'groups' => ['set.UserGroupBasic'],
                ],
            )
            ->willReturn('[]');

        new UserGroupsController($serializer)($user);
    }
}
