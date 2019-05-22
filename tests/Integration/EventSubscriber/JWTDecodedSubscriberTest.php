<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/JWTDecodedSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\JWTDecodedSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JWTDecodedSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTDecodedSubscriberTest extends KernelTestCase
{
    public function testThatJwtIsMarkedInvalidIfChecksumDiffers(): void
    {
        // Create pure Request
        $request = new Request();

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Create custom payload for JWTDecodedEvent
        $payload = [
            'checksum' => 'foobar'
        ];

        // Create event for subscriber
        $event = new JWTDecodedEvent($payload);

        // Create subscriber and call actual process method
        $subscriber = new JWTDecodedSubscriber($requestStack);
        $subscriber->onJWTDecoded($event);

        static::assertFalse($event->isValid(), 'JWTDecodedEvent did not mark event as invalid.');

        unset($subscriber, $event, $requestStack, $request);
    }

    public function testThatJwtIsNotMarkedInvalidIfChecksumMatches(): void
    {
        // Server parameters for new Request
        $server = [
            'REMOTE_ADDR'       => '123.123.123.123',
            'HTTP_USER_AGENT'   => 'foobar'
        ];

        // Create pure Request
        $request = new Request([], [], [], [], [], $server);

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Create custom payload for JWTDecodedEvent - this one is expected one
        $payload = [
            'checksum' => \hash('sha512', \implode('|', \array_values($server))),
        ];

        // Create event for subscriber
        $event = new JWTDecodedEvent($payload);

        // Create subscriber and call actual process method
        $subscriber = new JWTDecodedSubscriber($requestStack);
        $subscriber->onJWTDecoded($event);

        static::assertTrue($event->isValid(), 'JWTDecodedEvent did mark event as invalid.');

        unset($subscriber, $event, $requestStack, $request);
    }

    public function testThatEventIsMarkedInvalidIfRequestDoesNotExist(): void
    {
        // Create RequestStack
        $requestStack = new RequestStack();

        // Create custom payload for JWTDecodedEvent
        $payload = [];

        // Create event for subscriber
        $event = new JWTDecodedEvent($payload);

        /** @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        // Create subscriber and call actual process method
        $subscriber = new JWTDecodedSubscriber($requestStack);
        $subscriber->setLogger($logger);
        $subscriber->onJWTDecoded($event);

        static::assertFalse($event->isValid(), 'JWTDecodedEvent did not mark event as invalid.');

        unset($subscriber, $logger, $event, $requestStack);
    }

    public function testThatEventIsNotTouchedIfItHasAlreadyBeenMarkedInvalid(): void
    {
        // Create RequestStack
        $requestStack = new RequestStack();

        // Create custom payload for JWTDecodedEvent
        $payload = [];

        // Create event for subscriber
        $event = new JWTDecodedEvent($payload);
        $event->markAsInvalid();

        $expectedEvent = clone $event;

        // Create subscriber and call actual process method
        $subscriber = new JWTDecodedSubscriber($requestStack);
        $subscriber->onJWTDecoded($event);

        static::assertSame($expectedEvent->getPayload(), $event->getPayload());
        static::assertFalse($event->isValid());

        unset($subscriber, $expectedEvent, $event, $requestStack);
    }
}
