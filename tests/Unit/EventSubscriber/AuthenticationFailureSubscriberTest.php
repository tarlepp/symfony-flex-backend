<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/AuthenticationFailureSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthenticationFailureSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class AuthenticationFailureSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationFailureSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
        ];

        static::assertSame($expected, AuthenticationFailureSubscriber::getSubscribedEvents());
    }
}
