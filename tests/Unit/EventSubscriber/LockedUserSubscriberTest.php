<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LockedUserSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            'lexik_jwt_authentication.on_authentication_success' => [
                'onAuthenticationSuccess',
                128,
            ],
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure',
        ];

        static::assertSame($expected, LockedUserSubscriber::getSubscribedEvents());
    }
}
