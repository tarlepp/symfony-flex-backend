<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LockedUserSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            AuthenticationSuccessEvent::class => [
                'onAuthenticationSuccess',
                128,
            ],
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
        ];

        static::assertSame($expected, LockedUserSubscriber::getSubscribedEvents());
    }
}
