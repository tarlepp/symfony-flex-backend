<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/AuthenticationFailureSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationFailureSubscriber;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

/**
 * Class AuthenticationFailureSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationFailureSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureCallsExpectedServiceMethodsWhenUserPresent(): void
    {
        $user = (new User())
            ->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $authenticationException = new AuthenticationException();
        $authenticationException->setToken($token);

        $response = new Response();

        $event = new AuthenticationFailureEvent($authenticationException, $response);

        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with('test-user')
            ->willReturn($user);

        $loginLogger
            ->expects(self::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects(self::once())
            ->method('process');

        (new AuthenticationFailureSubscriber($loginLogger, $userRepository))
            ->onAuthenticationFailure($event);
    }

    public function testThatOnAuthenticationFailureCallsExpectedServiceMethodsWhenUserNotPresent(): void
    {
        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');

        $authenticationException = new AuthenticationException();
        $authenticationException->setToken($token);

        $response = new Response();

        $event = new AuthenticationFailureEvent($authenticationException, $response);

        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with('test-user')
            ->willReturn(null);

        $loginLogger
            ->expects(self::once())
            ->method('setUser')
            ->with(null)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects(self::once())
            ->method('process');

        $subscriber = new AuthenticationFailureSubscriber($loginLogger, $userRepository);

        try {
            $subscriber->onAuthenticationFailure($event);
        } catch (Throwable) {
        }
    }
}
