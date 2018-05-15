<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LockedUserSubscriber;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use Doctrine\Common\Collections\ArrayCollection;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testThatOnAuthenticationFailureCallsExpectedServiceMethod(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $authenticationException = new AuthenticationException();
        $authenticationException->setToken($token);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository          $userRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user);

        $logLoginFailureResource
            ->expects(static::once())
            ->method('save');

        $subscriber = new LockedUserSubscriber($userRepository, $logLoginFailureResource);
        $subscriber->onAuthenticationFailure(new AuthenticationFailureEvent($authenticationException, new Response()));
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testThatOnJWTAuthenticatedCallsExpectedServiceMethod(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository          $userRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user);

        $subscriber = new LockedUserSubscriber($userRepository, $logLoginFailureResource);
        $subscriber->onJWTAuthenticated(new JWTAuthenticatedEvent([], $token));
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testThatOnAuthenticationSuccessCallsExpectedServiceMethod(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');
        $event = new AuthenticationEvent($token);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository          $userRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user);

        $logLoginFailureResource
            ->expects(static::once())
            ->method('reset')
            ->with($user);

        $subscriber = new LockedUserSubscriber($userRepository, $logLoginFailureResource);
        $subscriber->onAuthenticationSuccess($event);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testThatOnJWTAuthenticatedDoesNotCallResetServiceMethodIfUserHasEnoughLoginFailures(): void
    {
        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');
        $event = new JWTAuthenticatedEvent([], $token);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository          $userRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();
        $user = $this->getMockBuilder(User::class)->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user);

        $logLoginFailureResource
            ->expects(static::never())
            ->method('reset')
            ->with($user);

        $user
            ->expects(static::once())
            ->method('getLogsLoginFailure')
            ->willReturn(new ArrayCollection(\range(0, 11)));

        $subscriber = new LockedUserSubscriber($userRepository, $logLoginFailureResource);
        $subscriber->onJWTAuthenticated($event);

        static::assertFalse($event->getToken()->isAuthenticated());
    }
}
