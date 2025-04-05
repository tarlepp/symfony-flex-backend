<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/DoctrineExtensionSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\DoctrineExtensionSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class DoctrineExtensionSubscriberTest extends KernelTestCase
{
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            RequestEvent::class => 'onKernelRequest',
        ];

        self::assertSame($expected, DoctrineExtensionSubscriber::getSubscribedEvents());
    }
}
