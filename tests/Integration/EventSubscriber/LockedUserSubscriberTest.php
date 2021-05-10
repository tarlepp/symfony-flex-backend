<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User as UserEntity;
use App\EventSubscriber\LockedUserSubscriber;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Rest\UuidHelper;
use App\Security\SecurityUser;
use Doctrine\Common\Collections\ArrayCollection;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\User as CoreUser;
use Throwable;
use function range;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessThrowsUserNotFoundException(): void
    {
        $event = new AuthenticationSuccessEvent([], new CoreUser('username', 'password'), new Response());

        [$userRepositoryMock, $logLoginFailureResourceMock] = $this->getMocks();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Unsupported user.');

        (new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock))
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessThrowsLockedException(): void
    {
        $uuid = UuidHelper::getFactory()->uuid1();

        [$userRepositoryMock, $logLoginFailureResourceMock] = $this->getMocks();
        $userMock = $this->getMockBuilder(UserEntity::class)->getMock();

        $userMock
            ->expects(static::exactly(2))
            ->method('getId')
            ->willReturn($uuid->toString());

        $userMock
            ->expects(static::once())
            ->method('getLogsLoginFailure')
            ->willReturn(new ArrayCollection(range(0, 11)));

        $userRepositoryMock
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($userMock->getId())
            ->willReturn($userMock);

        $securityUser = new SecurityUser($userMock);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('Locked account.');

        (new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock))
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessResourceResetMethodIsCalled(): void
    {
        $user = new UserEntity();

        [$userRepositoryMock, $logLoginFailureResourceMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($user->getId())
            ->willReturn($user);

        $logLoginFailureResourceMock
            ->expects(static::once())
            ->method('reset')
            ->with($user);

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        (new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock))
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureRepositoryAndResourceMethodsAreNotCalledWhenTokenIsNull(): void
    {
        [$userRepositoryMock, $logLoginFailureResourceMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::never())
            ->method(static::anything());

        $logLoginFailureResourceMock
            ->expects(static::never())
            ->method(static::anything());

        $event = new AuthenticationFailureEvent(new AuthenticationException(), new Response());

        (new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock))
            ->onAuthenticationFailure($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureTestThatResourceMethodsAreNotCalledWhenWrongUser(): void
    {
        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $exception = new AuthenticationException();
        $exception->setToken($token);

        [$userRepositoryMock, $logLoginFailureResourceMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn(null);

        $logLoginFailureResourceMock
            ->expects(static::never())
            ->method(static::anything());

        $event = new AuthenticationFailureEvent($exception, new Response());

        (new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock))
            ->onAuthenticationFailure($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureTestThatResourceSaveMethodIsCalled(): void
    {
        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $exception = new AuthenticationException();
        $exception->setToken($token);

        [$userRepositoryMock, $logLoginFailureResourceMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn(new UserEntity());

        $logLoginFailureResourceMock
            ->expects(static::once())
            ->method('save');

        $event = new AuthenticationFailureEvent($exception, new Response());

        (new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock))
            ->onAuthenticationFailure($event);
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&UserRepository,
     *      1: \PHPUnit\Framework\MockObject\MockObject&LogLoginFailureResource,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(LogLoginFailureResource::class)->disableOriginalConstructor()->getMock(),
        ];
    }
}
