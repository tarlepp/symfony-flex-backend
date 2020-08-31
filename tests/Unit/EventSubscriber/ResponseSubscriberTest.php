<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/RequestSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ResponseSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class RequestSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            ResponseEvent::class => [
                'onKernelResponse',
                10,
            ],
        ];

        static::assertSame($expected, ResponseSubscriber::getSubscribedEvents());
    }
}
