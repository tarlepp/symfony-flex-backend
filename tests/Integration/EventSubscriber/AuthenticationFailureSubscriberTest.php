<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/AuthenticationFailureSubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationFailureSubscriber;
use App\Security\UserProvider;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class AuthenticationFailureSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationFailureSubscriberTest extends KernelTestCase
{
    public function testThatOnAuthenticationFailureCallsExpectedServiceMethodsWhenUserPresent(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $authenticationException = new AuthenticationException();
        $authenticationException->setToken($token);

        $response = new Response();

        $event = new AuthenticationFailureEvent($authenticationException, $response);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LoginLogger  $loginLogger
         * @var \PHPUnit_Framework_MockObject_MockObject|UserProvider $userRepository
         */
        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserProvider::class)->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user);

        $loginLogger
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects(static::once())
            ->method('process');

        $subscriber = new AuthenticationFailureSubscriber($loginLogger, $userRepository);
        $subscriber->onAuthenticationFailure($event);
    }

    public function testThatOnAuthenticationFailureCallsExpectedServiceMethodsWhenUserNotPresent(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $authenticationException = new AuthenticationException();
        $authenticationException->setToken($token);

        $response = new Response();

        $event = new AuthenticationFailureEvent($authenticationException, $response);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LoginLogger  $loginLogger
         * @var \PHPUnit_Framework_MockObject_MockObject|UserProvider $userRepository
         */
        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserProvider::class)->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn(null);

        $loginLogger
            ->expects(static::once())
            ->method('setUser')
            ->with(null)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects(static::once())
            ->method('process');

        $subscriber = new AuthenticationFailureSubscriber($loginLogger, $userRepository);
        $subscriber->onAuthenticationFailure($event);
    }
}
