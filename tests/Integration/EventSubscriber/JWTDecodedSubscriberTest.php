<?php
declare(strict_types = 1);

/**
 * /tests/Integration/EventSubscriber/JWTDecodedSubscriberTest.php
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\JWTDecodedSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function array_values;
use function hash;
use function implode;

final class JWTDecodedSubscriberTest extends KernelTestCase
{
    public function testThatJwtIsMarkedInvalidIfChecksumDiffers(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        // Create custom payload for JWTDecodedEvent
        $payload = [
            'checksum' => 'foobar',
        ];

        // Create event for subscriber
        $event = new JWTDecodedEvent($payload);

        // Create subscriber and call actual process method
        new JWTDecodedSubscriber($requestStack, $loggerStub)
            ->onJWTDecoded($event);

        self::assertFalse($event->isValid(), 'JWTDecodedEvent did not mark event as invalid.');
    }

    public function testThatJwtIsNotMarkedInvalidIfChecksumMatches(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Server parameters for new Request
        $server = [
            'REMOTE_ADDR' => '123.123.123.123',
            'HTTP_USER_AGENT' => 'foobar',
        ];

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [], [], [], $server));

        // Create custom payload for JWTDecodedEvent - this one is expected one
        $payload = [
            'checksum' => hash('sha512', implode('|', array_values($server))),
        ];

        // Create event for subscriber
        $event = new JWTDecodedEvent($payload);

        // Create subscriber and call actual process method
        new JWTDecodedSubscriber($requestStack, $loggerStub)
            ->onJWTDecoded($event);

        self::assertTrue($event->isValid(), 'JWTDecodedEvent did mark event as invalid.');
    }

    public function testThatEventIsMarkedInvalidIfRequestDoesNotExist(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Create event for subscriber
        $event = new JWTDecodedEvent([]);

        // Create subscriber and call actual process method
        new JWTDecodedSubscriber(new RequestStack(), $loggerStub)
            ->onJWTDecoded($event);

        self::assertFalse($event->isValid(), 'JWTDecodedEvent did not mark event as invalid.');
    }

    public function testThatEventIsNotTouchedIfItHasAlreadyBeenMarkedInvalid(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Create event for subscriber
        $event = new JWTDecodedEvent([]);
        $event->markAsInvalid();

        $expectedEvent = clone $event;

        // Create subscriber and call actual process method
        new JWTDecodedSubscriber(new RequestStack(), $loggerStub)
            ->onJWTDecoded($event);

        self::assertSame($expectedEvent->getPayload(), $event->getPayload());
        self::assertFalse($event->isValid());
    }

    public function testThatLoggerIsCalledAndEventIsMarkedInvalidIfThereIsNoRequest(): void
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Request not available');

        $event = new JWTDecodedEvent([]);

        new JWTDecodedSubscriber(new RequestStack(), $loggerMock)
            ->onJWTDecoded($event);

        self::assertFalse($event->isValid());
    }
}
