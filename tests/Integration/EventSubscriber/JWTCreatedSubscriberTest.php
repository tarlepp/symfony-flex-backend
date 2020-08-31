<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/JWTCreatedSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\JWTCreatedSubscriber;
use App\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JWTCreatedSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTCreatedSubscriberTest extends KernelTestCase
{
    public function testThatPayloadContainsExpectedDataWhenRequestIsPresent(): void
    {
        /**
         * @var MockObject|LoggerInterface $logger
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        // Create new user for JWT
        $user = (new User())
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com');

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        (new JWTCreatedSubscriber($requestStack, $logger))
            ->onJWTCreated($event);

        $keys = ['exp', 'checksum'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }
    }

    public function testThatPayloadContainsExpectedDataWhenRequestIsNotPresent(): void
    {
        /**
         * @var MockObject|LoggerInterface $logger
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        // Create new user for JWT
        $user = (new User())
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com');

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        (new JWTCreatedSubscriber(new RequestStack(), $logger))
            ->onJWTCreated($event);

        $keys = ['exp'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }

        static::assertArrayNotHasKey('checksum', $event->getData());
    }

    public function testThatLoggerAlertIsCalledIfRequestDoesNotExist(): void
    {
        /**
         * @var MockObject|LoggerInterface $logger
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('alert')
            ->with('Request not available');

        // Create new user for JWT
        $user = (new User())
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com');

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        (new JWTCreatedSubscriber(new RequestStack(), $logger))
            ->onJWTCreated($event);
    }
}
