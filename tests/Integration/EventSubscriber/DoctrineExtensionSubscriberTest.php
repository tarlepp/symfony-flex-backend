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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use UnexpectedValueException;

/**
 * Class DoctrineExtensionSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DoctrineExtensionSubscriberTest extends KernelTestCase
{
    private MockObject | BlameableListener | null $blameableListener = null;
    private MockObject | UserTypeIdentification | null $userTypeIdentification = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blameableListener = $this->createMock(BlameableListener::class);
        $this->userTypeIdentification = $this->createMock(UserTypeIdentification::class);
    }

    /**
     * @testdox Test that user is not set to `BlameableListener` when there isn't logged in user
     *
     * @throws Throwable
     */
    public function testThatUserIsNotSetToBlameableListenerWhenThereIsNotLoggedInUser(): void
    {
        $this->getUserTypeIdentificationMock()
            ->expects(static::once())
            ->method('getUser')
            ->willReturn(null);

        $this->getBlameableListenerMock()
            ->expects(static::never())
            ->method('setUserValue');

        (new DoctrineExtensionSubscriber($this->getBlameableListener(), $this->getUserTypeIdentification()))
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

        $this->getUserTypeIdentificationMock()
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $this->getBlameableListenerMock()
            ->expects(static::once())
            ->method('setUserValue')
            ->with($user);

        (new DoctrineExtensionSubscriber($this->getBlameableListener(), $this->getUserTypeIdentification()))
            ->onKernelRequest();
    }

    private function getBlameableListener(): BlameableListener
    {
        return $this->blameableListener instanceof BlameableListener
            ? $this->blameableListener
            : throw new UnexpectedValueException('BlameableListener not set');
    }

    private function getBlameableListenerMock(): MockObject
    {
        return $this->blameableListener instanceof MockObject
            ? $this->blameableListener
            : throw new UnexpectedValueException('BlameableListener not set');
    }

    private function getUserTypeIdentification(): UserTypeIdentification
    {
        return $this->userTypeIdentification instanceof UserTypeIdentification
            ? $this->userTypeIdentification
            : throw new UnexpectedValueException('UserTypeIdentification not set');
    }

    private function getUserTypeIdentificationMock(): MockObject
    {
        return $this->userTypeIdentification instanceof MockObject
            ? $this->userTypeIdentification
            : throw new UnexpectedValueException('UserTypeIdentification not set');
    }
}
