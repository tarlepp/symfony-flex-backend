<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/AuthenticationSuccessSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthenticationSuccessSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class AuthenticationSuccessSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];

        self::assertSame($expected, AuthenticationSuccessSubscriber::getSubscribedEvents());
    }
}
