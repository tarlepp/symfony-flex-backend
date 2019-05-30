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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTCreatedSubscriberTest extends KernelTestCase
{
    public function testThatPayloadContainsExpectedDataWhenRequestIsPresent(): void
    {
        // Create empty JWT payload
        $payload = [];

        // Create new user for JWT
        $user = (new User())
            ->setFirstname('firstname')
            ->setSurname('surname')
            ->setEmail('firstname.surname@test.com');

        $securityUser = new SecurityUser($user);

        // Create pure Request
        $request = new Request();

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $securityUser);

        $subscriber = new JWTCreatedSubscriber($requestStack);
        $subscriber->onJWTCreated($event);

        $keys = ['exp', 'checksum'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }

        unset($subscriber, $event, $roles, $requestStack, $request, $user);
    }

    public function testThatPayloadContainsExpectedDataWhenRequestIsNotPresent(): void
    {
        // Create empty JWT payload
        $payload = [];

        // Create new user for JWT
        $user = (new User())
            ->setFirstname('firstname')
            ->setSurname('surname')
            ->setEmail('firstname.surname@test.com');

        $securityUser = new SecurityUser($user);

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();

        /**
         * @var MockObject|LoggerInterface $logger
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $securityUser);

        $subscriber = new JWTCreatedSubscriber($requestStack);
        $subscriber->setLogger($logger);
        $subscriber->onJWTCreated($event);

        $keys = ['exp'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }

        static::assertArrayNotHasKey('checksum', $event->getData());

        unset($subscriber, $event, $logger, $roles, $requestStack, $user);
    }

    public function testThatLoggerAlertIsCalledIfRequestDoesNotExist(): void
    {
        // Create empty JWT payload
        $payload = [];

        // Create new user for JWT
        $user = (new User())
            ->setFirstname('firstname')
            ->setSurname('surname')
            ->setEmail('firstname.surname@test.com');

        $securityUser = new SecurityUser($user);

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();

        /**
         * @var MockObject|LoggerInterface $logger
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('alert')
            ->with('Request not available');

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $securityUser);

        $subscriber = new JWTCreatedSubscriber($requestStack);
        $subscriber->setLogger($logger);
        $subscriber->onJWTCreated($event);

        unset($subscriber, $event, $logger, $roles, $requestStack, $user);
    }
}
