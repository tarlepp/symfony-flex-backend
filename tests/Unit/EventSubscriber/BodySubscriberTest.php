<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/BodySubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\BodySubscriber;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class BodySubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            RequestEvent::class => [
                'onKernelRequest',
                10,
            ],
        ];

        self::assertSame($expected, BodySubscriber::getSubscribedEvents());
    }
}
