<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/RequestLogSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RequestLogSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Class RequestLogSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLogSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            TerminateEvent::class => [
                'onTerminateEvent',
                15,
            ],
        ];

        static::assertSame($expected, RequestLogSubscriber::getSubscribedEvents());
    }
}
