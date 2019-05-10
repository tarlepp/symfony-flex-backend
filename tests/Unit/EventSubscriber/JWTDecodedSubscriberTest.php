<?php
declare(strict_types=1);
/**
 * /tests/Unit/EventSubscriber/JWTDecodedSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\JWTDecodedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class JWTDecodedSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTDecodedSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            'lexik_jwt_authentication.on_jwt_decoded' => 'onJWTDecoded',
        ];

        static::assertSame($expected, JWTDecodedSubscriber::getSubscribedEvents());
    }
}
