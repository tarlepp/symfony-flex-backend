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

/**
 * Class DoctrineExtensionSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DoctrineExtensionSubscriberTest extends KernelTestCase
{
    /**
     * @var MockObject|BlameableListener
     */
    private MockObject $blameableListener;

    /**
     * @var MockObject|UserTypeIdentification
     */
    private MockObject $userTypeIdentification;

    /**
     * @testdox Test that user is not set to `BlameableListener` when there isn't logged in user
     */
    public function testThatUserIsNotSetToBlameableListenerWhenThereIsNotLoggedInUser(): void
    {
        $this->userTypeIdentification
            ->expects(static::once())
            ->method('getUser')
            ->willReturn(null);

        $this->blameableListener
            ->expects(static::never())
            ->method('setUserValue');

        (new DoctrineExtensionSubscriber($this->blameableListener, $this->userTypeIdentification))->onKernelRequest();
    }

    /**
     * @testdox Test that user is set to `BlameableListener` when there is logged in user
     */
    public function testThatUserIsSetToBlameableListenerWhenThereIsLoggedInUser(): void
    {
        $user = new User();

        $this->userTypeIdentification
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $this->blameableListener
            ->expects(static::once())
            ->method('setUserValue')
            ->with($user);

        (new DoctrineExtensionSubscriber($this->blameableListener, $this->userTypeIdentification))->onKernelRequest();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->blameableListener = $this->createMock(BlameableListener::class);
        $this->userTypeIdentification = $this->createMock(UserTypeIdentification::class);
    }
}
