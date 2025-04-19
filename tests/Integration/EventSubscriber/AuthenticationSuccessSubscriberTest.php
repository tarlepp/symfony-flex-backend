<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/AuthenticationSuccessSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationSuccessSubscriber;
use App\Repository\UserRepository;
use App\Security\SecurityUser;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationSuccessSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessMethodCallsExpectedLoggerMethods(): void
    {
        $userEntity = new User()->setUsername('test_user');
        $securityUser = new SecurityUser($userEntity);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $loginLogger
            ->expects($this->once())
            ->method('setUser')
            ->with($userEntity)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects($this->once())
            ->method('process');

        $userRepository
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($userEntity->getId())
            ->willReturn($userEntity);

        new AuthenticationSuccessSubscriber($loginLogger, $userRepository)
            ->onAuthenticationSuccess($event);
    }
}
