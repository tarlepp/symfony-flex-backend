<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/AuthenticationSuccessSubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationSuccessSubscriber;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticationSuccessSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationSuccessSubscriberTest extends KernelTestCase
{
    public function testThatOnAuthenticationSuccessMethodCallsExpectedLoggerMethods(): void
    {
        $user = new User();
        $event = new AuthenticationSuccessEvent([], $user, new Response());

        /** @var \PHPUnit_Framework_MockObject_MockObject|LoginLogger $loginLogger */
        $loginLogger = $this->getMockBuilder(LoginLogger::class)->disableOriginalConstructor()->getMock();

        $loginLogger
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($loginLogger);

        $loginLogger
            ->expects(static::once())
            ->method('process');

        $subscriber = new AuthenticationSuccessSubscriber($loginLogger);
        $subscriber->onAuthenticationSuccess($event);
    }
}
