<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/BodySubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\BodySubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class BodySubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class BodySubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            RequestEvent::class => [
                'onKernelRequest',
                10,
            ],
        ];

        static::assertSame($expected, BodySubscriber::getSubscribedEvents());
    }
}
