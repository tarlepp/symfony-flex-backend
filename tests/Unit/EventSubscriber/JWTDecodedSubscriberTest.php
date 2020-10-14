<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/JWTDecodedSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\JWTDecodedSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class JWTDecodedSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class JWTDecodedSubscriberTest extends KernelTestCase
{
    /**
     * @testdox Test that `getSubscribedEvents` method returns expected
     */
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            JWTDecodedEvent::class => 'onJWTDecoded',
            'lexik_jwt_authentication.on_jwt_decoded' => 'onJWTDecoded',
        ];

        static::assertSame($expected, JWTDecodedSubscriber::getSubscribedEvents());
    }
}
