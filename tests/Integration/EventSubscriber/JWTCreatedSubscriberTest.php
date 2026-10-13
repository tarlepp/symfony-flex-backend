<?php
declare(strict_types = 1);

/**
 * /tests/Integration/EventSubscriber/JWTCreatedSubscriberTest.php
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\Enum\Language;
use App\Enum\Locale;
use App\EventSubscriber\JWTCreatedSubscriber;
use App\Security\SecurityUser;
use App\Service\Localization;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class JWTCreatedSubscriberTest extends KernelTestCase
{
    public function testThatPayloadContainsExpectedDataWhenRequestIsPresent(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Create new user for JWT
        $user = new User()
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com');

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        new JWTCreatedSubscriber($requestStack, $loggerStub)
            ->onJWTCreated($event);

        $keys = ['exp', 'checksum'];

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $event->getData());
        }
    }

    public function testThatPayloadContainsExpectedDataWhenRequestIsNotPresent(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Create new user for JWT
        $user = new User()
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com');

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        new JWTCreatedSubscriber(new RequestStack(), $loggerStub)
            ->onJWTCreated($event);

        $keys = ['exp'];

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $event->getData());
        }

        self::assertArrayNotHasKey('checksum', $event->getData());
    }

    public function testThatLoggerAlertIsCalledIfRequestDoesNotExist(): void
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with('Request not available');

        // Create new user for JWT
        $user = new User()
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com');

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        new JWTCreatedSubscriber(new RequestStack(), $loggerMock)
            ->onJWTCreated($event);
    }

    public function testThatExpectedLocalizationDataIsSetWhenUsingSecurityUser(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        // Create new user for JWT
        $user = new User()
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com')
            ->setLanguage(Language::FI)
            ->setLocale(Locale::FI)
            ->setTimezone('UTC');

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], new SecurityUser($user));

        new JWTCreatedSubscriber($requestStack, $loggerStub)
            ->onJWTCreated($event);

        $keys = ['language', 'locale', 'timezone'];

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $event->getData());
        }

        self::assertSame(Language::FI->value, $event->getData()['language']);
        self::assertSame(Locale::FI->value, $event->getData()['locale']);
        self::assertSame('UTC', $event->getData()['timezone']);
    }

    public function testThatDefaultLocalizationDataIsSetWhenNotUsingSecurityUser(): void
    {
        $loggerStub = self::createStub(LoggerInterface::class);

        $user = new InMemoryUser('user', 'password');

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent([], $user);

        new JWTCreatedSubscriber($requestStack, $loggerStub)
            ->onJWTCreated($event);

        $keys = ['language', 'locale', 'timezone'];

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $event->getData());
        }

        self::assertSame(Language::getDefault()->value, $event->getData()['language']);
        self::assertSame(Locale::getDefault()->value, $event->getData()['locale']);
        self::assertSame(Localization::DEFAULT_TIMEZONE, $event->getData()['timezone']);
    }
}
