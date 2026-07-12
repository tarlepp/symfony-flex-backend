<?php
declare(strict_types = 1);

/**
 * /tests/Unit/EventSubscriber/JWTDecodedSubscriberTest.php
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\JWTDecodedSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class JWTDecodedSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            JWTDecodedEvent::class => 'onJWTDecoded',
            'lexik_jwt_authentication.on_jwt_decoded' => 'onJWTDecoded',
        ];

        self::assertSame($expected, JWTDecodedSubscriber::getSubscribedEvents());
    }
}
