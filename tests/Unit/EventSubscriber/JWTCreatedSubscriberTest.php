<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/JWTCreatedSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\JWTCreatedSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class JWTCreatedSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            JWTCreatedEvent::class => 'onJWTCreated',
            'lexik_jwt_authentication.on_jwt_created' => 'onJWTCreated',
        ];

        self::assertSame($expected, JWTCreatedSubscriber::getSubscribedEvents());
    }
}
