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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class DoctrineExtensionSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DoctrineExtensionSubscriberTest extends KernelTestCase
{
    /**
     * @testdox Test that user is not set to `BlameableListener` when there isn't logged in user
     *
     * @throws Throwable
     */
    public function testThatUserIsNotSetToBlameableListenerWhenThereIsNotLoggedInUser(): void
    {
        [$blameableListenerMock, $userTypeIdentificationMock] = $this->getMocks();

        $userTypeIdentificationMock
            ->expects(static::once())
            ->method('getUser')
            ->willReturn(null);

        $blameableListenerMock
            ->expects(static::never())
            ->method('setUserValue');

        (new DoctrineExtensionSubscriber($blameableListenerMock, $userTypeIdentificationMock))
            ->onKernelRequest();
    }

    /**
     * @testdox Test that user is set to `BlameableListener` when there is logged in user
     *
     * @throws Throwable
     */
    public function testThatUserIsSetToBlameableListenerWhenThereIsLoggedInUser(): void
    {
        $user = new User();

        [$blameableListenerMock, $userTypeIdentificationMock] = $this->getMocks();

        $userTypeIdentificationMock
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $blameableListenerMock
            ->expects(static::once())
            ->method('setUserValue')
            ->with($user);

        (new DoctrineExtensionSubscriber($blameableListenerMock, $userTypeIdentificationMock))
            ->onKernelRequest();
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&BlameableListener,
     *      1: \PHPUnit\Framework\MockObject\MockObject&UserTypeIdentification,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->createMock(BlameableListener::class),
            $this->createMock(UserTypeIdentification::class),
        ];
    }
}
