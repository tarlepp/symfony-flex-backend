<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/ResponseSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ResponseSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class ResponseSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ResponseSubscriberTest extends KernelTestCase
{
    /**
     * @testdox Test that `getSubscribedEvents` method returns expected
     */
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            ResponseEvent::class => [
                'onKernelResponse',
                10,
            ],
        ];

        self::assertSame($expected, ResponseSubscriber::getSubscribedEvents());
    }
}
