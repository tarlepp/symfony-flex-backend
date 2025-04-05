<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/DoctrineExtensionSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\DoctrineExtensionSubscriber;
use App\Security\UserTypeIdentification;
use Gedmo\Blameable\BlameableListener;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class DoctrineExtensionSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that user is not set to `BlameableListener` when there isn't logged-in user")]
    public function testThatUserIsNotSetToBlameableListenerWhenThereIsNotLoggedInUser(): void
    {
        $userTypeIdentification = $this->getUserTypeIdentification();
        $blameableListener = $this->getBlameableListener();

        $userTypeIdentification
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $blameableListener
            ->expects(self::never())
            ->method('setUserValue');

        new DoctrineExtensionSubscriber($blameableListener, $userTypeIdentification)
            ->onKernelRequest();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that user is set to `BlameableListener` when there is logged in user')]
    public function testThatUserIsSetToBlameableListenerWhenThereIsLoggedInUser(): void
    {
        $userTypeIdentification = $this->getUserTypeIdentification();
        $blameableListener = $this->getBlameableListener();

        $user = new User();

        $userTypeIdentification
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $blameableListener
            ->expects($this->once())
            ->method('setUserValue')
            ->with($user);

        new DoctrineExtensionSubscriber($blameableListener, $userTypeIdentification)
            ->onKernelRequest();
    }

    /**
     * @phpstan-return MockObject&BlameableListener
     */
    private function getBlameableListener(): BlameableListener
    {
        return $this->createMock(BlameableListener::class);
    }

    /**
     * @phpstan-return MockObject&UserTypeIdentification
     */
    private function getUserTypeIdentification(): MockObject
    {
        return $this->createMock(UserTypeIdentification::class);
    }
}
