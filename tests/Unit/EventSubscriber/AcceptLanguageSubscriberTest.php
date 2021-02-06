<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/AcceptLanguageSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AcceptLanguageSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AcceptLanguageSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AcceptLanguageSubscriberTest extends KernelTestCase
{
    /**
     * @testdox Test that `getSubscribedEvents` method returns expected
     */
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            RequestEvent::class => [
                'onKernelRequest',
                100,
            ],
        ];

        static::assertSame($expected, AcceptLanguageSubscriber::getSubscribedEvents());
    }
}
