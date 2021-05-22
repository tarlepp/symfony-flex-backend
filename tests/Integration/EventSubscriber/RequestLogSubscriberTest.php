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
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Utils\RequestLogger;
use Generator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Throwable;

/**
 * Class RequestLogSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLogSubscriberTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that all expected methods are called from `RequestLogger` class
     */
    public function testThatMethodCallsExpectedLoggerMethods(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();
        $event = new TerminateEvent(static::$kernel, $request, $response);

        [$requestLoggerMock, $userRepositoryMock, $loggerMock, $userTypeIdentificationMock] = $this->getMocks();

        $requestLoggerMock
            ->expects(static::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLoggerMock);

        $requestLoggerMock
            ->expects(static::once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($requestLoggerMock);

        $requestLoggerMock
            ->expects(static::once())
            ->method('setMasterRequest')
            ->with($event->isMasterRequest())
            ->willReturn($requestLoggerMock);

        $requestLoggerMock
            ->expects(static::once())
            ->method('handle');

        $requestLoggerMock
            ->expects(static::never())
            ->method('setUser');

        $requestLoggerMock
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $requestLoggerMock,
            $userRepositoryMock,
            $loggerMock,
            $userTypeIdentificationMock,
            []
        ))
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `RequestLogger::setUser` method is called
     */
    public function testThatSetUserIsCalled(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();
        $event = new TerminateEvent(static::$kernel, $request, $response);
        $user = (new User())->setUsername('test user');
        $securityUser = new SecurityUser($user);

        [$requestLoggerMock, $userRepositoryMock, $loggerMock, $userTypeIdentificationMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $userTypeIdentificationMock
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $requestLoggerMock
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($requestLoggerMock);

        $requestLoggerMock
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $requestLoggerMock,
            $userRepositoryMock,
            $loggerMock,
            $userTypeIdentificationMock,
            []
        ))
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `LoggerInterface::error` method is called if user is not found from database
     */
    public function testThatLoggerIsCalledIfUserIsNotFoundByRepository(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();
        $event = new TerminateEvent(static::$kernel, $request, $response);
        $user = (new User())->setUsername('test user');
        $securityUser = new SecurityUser($user);

        [$requestLoggerMock, $userRepositoryMock, $loggerMock, $userTypeIdentificationMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn(null);

        $userTypeIdentificationMock
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $requestLoggerMock
            ->expects(static::never())
            ->method('setUser');

        $loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf('User not found for UUID: "%s".', $user->getId()),
                RequestLogSubscriber::getSubscribedEvents()
            );

        (new RequestLogSubscriber(
            $requestLoggerMock,
            $userRepositoryMock,
            $loggerMock,
            $userTypeIdentificationMock,
            []
        ))
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `RequestLogger::setApiKey` method is called
     */
    public function testThatSetApiKeyIsCalled(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();
        $event = new TerminateEvent(static::$kernel, $request, $response);
        $apiKey = new ApiKey();

        [$requestLoggerMock, $userRepositoryMock, $loggerMock, $userTypeIdentificationMock] = $this->getMocks();
        $apiKeyUserMock = $this->getMockBuilder(ApiKeyUser::class)->disableOriginalConstructor()->getMock();

        $apiKeyUserMock
            ->expects(static::once())
            ->method('getApiKey')
            ->willReturn($apiKey);

        $userTypeIdentificationMock
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($apiKeyUserMock);

        $requestLoggerMock
            ->expects(static::once())
            ->method('setApiKey')
            ->with($apiKey)
            ->willReturn($requestLoggerMock);

        $requestLoggerMock
            ->expects(static::never())
            ->method('setUser');

        (new RequestLogSubscriber(
            $requestLoggerMock,
            $userRepositoryMock,
            $loggerMock,
            $userTypeIdentificationMock,
            []
        ))
            ->onTerminateEvent($event);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that any methods of `RequestLogger` service aren't called when request method is `OPTIONS`
     */
    public function testThatLoggerServiceIsNotCalledIfOptionsRequest(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/foobar',
        ]);
        $response = new Response();
        $event = new TerminateEvent(static::$kernel, $request, $response);

        [$requestLoggerMock, $userRepositoryMock, $loggerMock, $userTypeIdentificationMock] = $this->getMocks();

        $requestLoggerMock
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLoggerMock);

        (new RequestLogSubscriber(
            $requestLoggerMock,
            $userRepositoryMock,
            $loggerMock,
            $userTypeIdentificationMock,
            []
        ))
            ->onTerminateEvent($event);
    }

    /**
     * @dataProvider dataProviderTestThatLoggerServiceIsNotCalledWhenUsingWhitelistedWildcard
     *
     * @throws Throwable
     *
     * @testdox Test that `RequestLogger` service isn't used when making request to `$url` with `$ignored` ignored route
     */
    public function testThatLoggerServiceIsNotCalledWhenUsingSpecifiedIgnoredRoute(string $url, string $ignored): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $url]);
        $response = new Response();
        $event = new TerminateEvent(static::$kernel, $request, $response);

        [$requestLoggerMock, $userRepositoryMock, $loggerMock, $userTypeIdentificationMock] = $this->getMocks();

        $requestLoggerMock
            ->expects(static::never())
            ->method('setRequest');

        $requestLoggerMock
            ->expects(static::never())
            ->method('setResponse');

        $userTypeIdentificationMock
            ->expects(static::never())
            ->method('getIdentity');

        $requestLoggerMock
            ->expects(static::never())
            ->method('setMasterRequest');

        $requestLoggerMock
            ->expects(static::never())
            ->method('handle');

        $requestLoggerMock
            ->expects(static::never())
            ->method('setUser');

        $requestLoggerMock
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $requestLoggerMock,
            $userRepositoryMock,
            $loggerMock,
            $userTypeIdentificationMock,
            [$ignored]
        ))
            ->onTerminateEvent($event);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatLoggerServiceIsNotCalledWhenUsingWhitelistedWildcard(): Generator
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
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&RequestLogger,
     *      1: \PHPUnit\Framework\MockObject\MockObject&UserRepository,
     *      2: \PHPUnit\Framework\MockObject\MockObject&LoggerInterface,
     *      3: \PHPUnit\Framework\MockObject\MockObject&UserTypeIdentification,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->getMock(),
            $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock(),
        ];
    }
}
