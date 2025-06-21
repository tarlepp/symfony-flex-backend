<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/RequestLogSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\ApiKey;
use App\Entity\User;
use App\EventSubscriber\RequestLogSubscriber;
use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Utils\RequestLogger;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Throwable;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLogSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that all expected methods are called from `RequestLogger` class')]
    public function testThatMethodCallsExpectedLoggerMethods(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_URI' => '/foobar',
            ]
        );
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);
        $requestLogger = $this->getRequestLogger();
        $userTypeIdentification = $this->getUserTypeIdentification();

        $requestLogger
            ->expects($this->once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLogger);

        $requestLogger
            ->expects($this->once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($requestLogger);

        $requestLogger
            ->expects($this->once())
            ->method('setMainRequest')
            ->with($event->isMainRequest())
            ->willReturn($requestLogger);

        $requestLogger
            ->expects($this->once())
            ->method('handle');

        $requestLogger
            ->expects(self::never())
            ->method('setUserId');

        $requestLogger
            ->expects(self::never())
            ->method('setApiKeyId');

        new RequestLogSubscriber(
            $requestLogger,
            $userTypeIdentification,
            []
        )
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `RequestLogger::setUserId` method is called')]
    public function testThatSetUserIsCalled(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_URI' => '/foobar',
            ]
        );
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);
        $user = new User();
        $securityUser = new SecurityUser($user);
        $requestLogger = $this->getRequestLogger();
        $userTypeIdentification = $this->getUserTypeIdentification();

        $userTypeIdentification
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $requestLogger
            ->expects($this->once())
            ->method('setUserId')
            ->with($user->getId())
            ->willReturn($requestLogger);

        $requestLogger
            ->expects(self::never())
            ->method('setApiKeyId');

        new RequestLogSubscriber(
            $requestLogger,
            $userTypeIdentification,
            []
        )
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `RequestLogger::setApiKeyId` method is called')]
    public function testThatSetApiKeyIsCalled(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_URI' => '/foobar',
            ]
        );
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);
        $apiKey = new ApiKey();
        $apiKeyUser = new ApiKeyUser($apiKey, []);
        $requestLogger = $this->getRequestLogger();
        $userTypeIdentification = $this->getUserTypeIdentification();

        $userTypeIdentification
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn($apiKeyUser);

        $requestLogger
            ->expects($this->once())
            ->method('setApiKeyId')
            ->with($apiKey->getId())
            ->willReturn($requestLogger);

        $requestLogger
            ->expects(self::never())
            ->method('setUserId');

        new RequestLogSubscriber(
            $requestLogger,
            $userTypeIdentification,
            []
        )
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that any methods of `RequestLogger` service aren't called when request method is `OPTIONS`")]
    public function testThatLoggerServiceIsNotCalledIfOptionsRequest(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/foobar',
        ]);
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);
        $requestLogger = $this->getRequestLogger();
        $userTypeIdentification = $this->getUserTypeIdentification();

        $requestLogger
            ->expects(self::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLogger);

        new RequestLogSubscriber(
            $requestLogger,
            $userTypeIdentification,
            []
        )
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatLoggerServiceIsNotCalledWhenUsingWhitelistedWildcard')]
    #[TestDox(
        'Test that `RequestLogger` service isn\'t used when making request to `$url` with `$ignored` ignored route'
    )]
    public function testThatLoggerServiceIsNotCalledWhenUsingSpecifiedIgnoredRoute(string $url, string $ignored): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_URI' => $url,
            ]
        );
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);
        $requestLogger = $this->getRequestLogger();
        $userTypeIdentification = $this->getUserTypeIdentification();

        $requestLogger
            ->expects(self::never())
            ->method('setRequest');

        $requestLogger
            ->expects(self::never())
            ->method('setResponse');

        $userTypeIdentification
            ->expects(self::never())
            ->method('getIdentity');

        $requestLogger
            ->expects(self::never())
            ->method('setMainRequest');

        $requestLogger
            ->expects(self::never())
            ->method('handle');

        $requestLogger
            ->expects(self::never())
            ->method('setUserId');

        $requestLogger
            ->expects(self::never())
            ->method('setApiKeyId');

        new RequestLogSubscriber(
            $requestLogger,
            $userTypeIdentification,
            [$ignored]
        )
            ->onTerminateEvent($event);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderTestThatLoggerServiceIsNotCalledWhenUsingWhitelistedWildcard(): Generator
    {
        yield ['/', '/'];
        yield ['/healthz', '/healthz'];
        yield ['/version', '/version'];
        yield ['/profiler', '/profiler'];
        yield ['/secret', '/secret/*'];
        yield ['/secret/foo', '/secret/*'];
        yield ['/secret/foo/bar', '/secret/*'];
    }

    /**
     * @psalm-return MockObject&RequestLogger
     */
    private function getRequestLogger(): MockObject
    {
        return $this->getMockBuilder(RequestLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @psalm-return MockObject&UserTypeIdentification
     */
    private function getUserTypeIdentification(): MockObject
    {
        return $this->getMockBuilder(UserTypeIdentification::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
