<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/AuthenticationSuccessSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthenticationSuccessSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class AuthenticationSuccessSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationSuccessSubscriberTest extends KernelTestCase
{
    /**
     * @testdox Test that `getSubscribedEvents` method returns expected
     */
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            'Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent' => 'onAuthenticationSuccess',
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];

        self::assertSame($expected, AuthenticationSuccessSubscriber::getSubscribedEvents());
    }
}
