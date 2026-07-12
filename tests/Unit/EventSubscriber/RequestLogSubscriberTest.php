<?php
declare(strict_types = 1);

/**
 * /tests/Unit/EventSubscriber/RequestLogSubscriberTest.php
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RequestLogSubscriber;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class RequestLogSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            TerminateEvent::class => [
                'onTerminateEvent',
                15,
            ],
        ];

        self::assertSame($expected, RequestLogSubscriber::getSubscribedEvents());
    }
}
