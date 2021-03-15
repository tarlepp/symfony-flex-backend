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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Throwable;
use UnexpectedValueException;

/**
 * Class RequestLogSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLogSubscriberTest extends KernelTestCase
{
    private MockObject | RequestLogger | null $requestLogger = null;
    private MockObject | UserRepository | null $userRepository = null;
    private MockObject | LoggerInterface | null $logger = null;
    private MockObject | UserTypeIdentification | null $userTypeIdentification = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->requestLogger = $this->getMockBuilder(RequestLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->userTypeIdentification = $this->getMockBuilder(UserTypeIdentification::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $this->getRequestLoggerMock()
            ->expects(static::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($this->requestLogger);

        $this->getRequestLoggerMock()
            ->expects(static::once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($this->requestLogger);

        $this->getRequestLoggerMock()
            ->expects(static::once())
            ->method('setMasterRequest')
            ->with($event->isMasterRequest())
            ->willReturn($this->requestLogger);

        $this->getRequestLoggerMock()
            ->expects(static::once())
            ->method('handle');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setUser');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $this->getRequestLogger(),
            $this->getUserRepository(),
            $this->getLogger(),
            $this->getUserTypeIdentification(),
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
        $user = (new User())
            ->setUsername('test user');

        $securityUser = new SecurityUser($user);

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $this->getUserTypeIdentificationMock()
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $this->getRequestLoggerMock()
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($this->requestLogger);

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $this->getRequestLogger(),
            $this->getUserRepository(),
            $this->getLogger(),
            $this->getUserTypeIdentification(),
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
        $user = (new User())
            ->setUsername('test user');

        $securityUser = new SecurityUser($user);

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn(null);

        $this->getUserTypeIdentificationMock()
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setUser');

        $this->getLoggerMock()
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf('User not found for UUID: "%s".', $user->getId()),
                RequestLogSubscriber::getSubscribedEvents()
            );

        (new RequestLogSubscriber(
            $this->getRequestLogger(),
            $this->getUserRepository(),
            $this->getLogger(),
            $this->getUserTypeIdentification(),
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

        $apiKeyUser = $this->getMockBuilder(ApiKeyUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKey = new ApiKey();

        $apiKeyUser
            ->expects(static::once())
            ->method('getApiKey')
            ->willReturn($apiKey);

        $this->getUserTypeIdentificationMock()
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($apiKeyUser);

        $this->getRequestLoggerMock()
            ->expects(static::once())
            ->method('setApiKey')
            ->with($apiKey)
            ->willReturn($this->requestLogger);

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setUser');

        (new RequestLogSubscriber(
            $this->getRequestLogger(),
            $this->getUserRepository(),
            $this->getLogger(),
            $this->getUserTypeIdentification(),
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

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($this->requestLogger);

        (new RequestLogSubscriber(
            $this->getRequestLogger(),
            $this->getUserRepository(),
            $this->getLogger(),
            $this->getUserTypeIdentification(),
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

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setRequest');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setResponse');

        $this->getUserTypeIdentificationMock()
            ->expects(static::never())
            ->method('getIdentity');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setMasterRequest');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('handle');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setUser');

        $this->getRequestLoggerMock()
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $this->getRequestLogger(),
            $this->getUserRepository(),
            $this->getLogger(),
            $this->getUserTypeIdentification(),
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

    private function getRequestLogger(): RequestLogger
    {
        return $this->requestLogger instanceof RequestLogger
            ? $this->requestLogger
            : throw new UnexpectedValueException('RequestLogger not set');
    }

    private function getRequestLoggerMock(): MockObject
    {
        return $this->requestLogger instanceof MockObject
            ? $this->requestLogger
            : throw new UnexpectedValueException('RequestLogger not set');
    }

    private function getUserRepository(): UserRepository
    {
        return $this->userRepository instanceof UserRepository
            ? $this->userRepository
            : throw new UnexpectedValueException('UserRepository not set');
    }

    private function getUserRepositoryMock(): MockObject
    {
        return $this->userRepository instanceof MockObject
            ? $this->userRepository
            : throw new UnexpectedValueException('UserRepository not set');
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger instanceof LoggerInterface
            ? $this->logger
            : throw new UnexpectedValueException('Logger not set');
    }

    private function getLoggerMock(): MockObject
    {
        return $this->logger instanceof MockObject
            ? $this->logger
            : throw new UnexpectedValueException('Logger not set');
    }

    private function getUserTypeIdentification(): UserTypeIdentification
    {
        return $this->userTypeIdentification instanceof UserTypeIdentification
            ? $this->userTypeIdentification
            : throw new UnexpectedValueException('UserTypeIdentification not set');
    }

    private function getUserTypeIdentificationMock(): MockObject
    {
        return $this->userTypeIdentification instanceof MockObject
            ? $this->userTypeIdentification
            : throw new UnexpectedValueException('UserTypeIdentification not set');
    }
}
