<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/BodySubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Class BodySubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ExceptionSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            ExceptionEvent::class => [
                'onKernelException',
                -100,
            ],
        ];

        static::assertSame($expected, ExceptionSubscriber::getSubscribedEvents());
    }
}
