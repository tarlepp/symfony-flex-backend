<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
use PHPUnit\Framework\MockObject\MockObject;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessThrowsUserNotFoundException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Unsupported user.');

        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $event = new AuthenticationSuccessEvent([], new CoreUser('username', 'password'), new Response());

        (new LockedUserSubscriber($userRepository, $logLoginFailureResource))
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessThrowsLockedException(): void
    {
        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('Locked account.');

        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         * @var MockObject|UserEntity $user
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(UserEntity::class)->getMock();

        $uuid = UuidHelper::getFactory()->uuid1();

        $user
            ->expects(static::exactly(2))
            ->method('getId')
            ->willReturn($uuid->toString());

        $user
            ->expects(static::once())
            ->method('getLogsLoginFailure')
            ->willReturn(new ArrayCollection(range(0, 11)));

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($user->getId())
            ->willReturn($user);

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        (new LockedUserSubscriber($userRepository, $logLoginFailureResource))
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessResourceResetMethodIsCalled(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $user = new UserEntity();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($user->getId())
            ->willReturn($user);

        $logLoginFailureResource
            ->expects(static::once())
            ->method('reset')
            ->with($user);

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        (new LockedUserSubscriber($userRepository, $logLoginFailureResource))
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureRepositoryAndResourceMethodsAreNotCalledWhenTokenIsNull(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepository
            ->expects(static::never())
            ->method(static::anything());

        $logLoginFailureResource
            ->expects(static::never())
            ->method(static::anything());

        $event = new AuthenticationFailureEvent(new AuthenticationException(), new Response());

        (new LockedUserSubscriber($userRepository, $logLoginFailureResource))
            ->onAuthenticationFailure($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureTestThatResourceMethodsAreNotCalledWhenWrongUser(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $exception = new AuthenticationException();
        $exception->setToken($token);

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn(null);

        $logLoginFailureResource
            ->expects(static::never())
            ->method(static::anything());

        $event = new AuthenticationFailureEvent($exception, new Response());

        (new LockedUserSubscriber($userRepository, $logLoginFailureResource))
            ->onAuthenticationFailure($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureTestThatResourceSaveMethodIsCalled(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $exception = new AuthenticationException();
        $exception->setToken($token);

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn(new UserEntity());

        $logLoginFailureResource
            ->expects(static::once())
            ->method('save');

        $event = new AuthenticationFailureEvent($exception, new Response());

        (new LockedUserSubscriber($userRepository, $logLoginFailureResource))
            ->onAuthenticationFailure($event);
    }
}
