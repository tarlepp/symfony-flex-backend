<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/RequestSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RequestSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            'kernel.response' => [
                'onKernelResponse',
                15,
            ],
        ];

        static::assertSame($expected, RequestSubscriber::getSubscribedEvents());
    }
}
