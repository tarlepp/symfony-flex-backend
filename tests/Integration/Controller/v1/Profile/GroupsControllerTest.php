<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Profile/GroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Profile;

use App\Controller\v1\Profile\GroupsController;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class GroupsControllerTest
 *
 * @package App\Tests\Integration\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GroupsControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `__invoke(User $loggedInUser)` method calls expected service methods
     */
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $user = new User();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with(
                $user->getUserGroups()->toArray(),
                'json',
                [
                    'groups' => 'set.UserProfileGroups',
                ],
            )
            ->willReturn('{}');

        (new GroupsController($serializer))($user);
    }
}
