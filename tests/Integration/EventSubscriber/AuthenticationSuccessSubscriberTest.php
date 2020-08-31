<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/AuthenticationSuccessSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationSuccessSubscriber;
use App\Repository\UserRepository;
use App\Security\SecurityUser;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class AuthenticationSuccessSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationSuccessSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessMethodCallsExpectedLoggerMethods(): void
    {
        $userEntity = (new User())->setUsername('test_user');
        $securityUser = new SecurityUser($userEntity);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        /**
         * @var MockObject|LoginLogger $loginLogger
         * @var MockObject|UserRepository $userRepository
         */
        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $loginLogger
            ->expects(static::once())
            ->method('setUser')
            ->with($userEntity)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects(static::once())
            ->method('process');

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($userEntity->getId())
            ->willReturn($userEntity);

        (new AuthenticationSuccessSubscriber($loginLogger, $userRepository))
            ->onAuthenticationSuccess($event);
    }
}
