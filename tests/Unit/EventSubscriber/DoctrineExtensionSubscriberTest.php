<?php
declare(strict_types = 1);

/**
 * /tests/Unit/EventSubscriber/DoctrineExtensionSubscriberTest.php
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\DoctrineExtensionSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;

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
