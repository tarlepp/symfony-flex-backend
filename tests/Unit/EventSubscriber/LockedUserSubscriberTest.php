<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LockedUserSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @testdox Test that `getSubscribedEvents` method returns expected
     */
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            'Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent' => [
                'onAuthenticationSuccess',
                128,
            ],
            'Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent' => 'onAuthenticationFailure',
            'lexik_jwt_authentication.on_authentication_success' => [
                'onAuthenticationSuccess',
                128,
            ],
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure',
        ];

        static::assertSame($expected, LockedUserSubscriber::getSubscribedEvents());
    }
}
