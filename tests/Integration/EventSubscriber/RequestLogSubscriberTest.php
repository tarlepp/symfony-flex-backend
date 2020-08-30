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

/**
 * Class RequestLogSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLogSubscriberTest extends KernelTestCase
{
    /**
     * @var MockObject|RequestLogger
     */
    private MockObject $requestLogger;

    /**
     * @var MockObject|UserRepository
     */
    private MockObject $userRepository;

    /**
     * @var MockObject|LoggerInterface
     */
    private MockObject $logger;

    /**
     * @var MockObject|UserTypeIdentification
     */
    private MockObject $userTypeIdentification;

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

        $this->requestLogger
            ->expects(static::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($this->requestLogger);

        $this->requestLogger
            ->expects(static::once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($this->requestLogger);

        $this->requestLogger
            ->expects(static::once())
            ->method('setMasterRequest')
            ->with($event->isMasterRequest())
            ->willReturn($this->requestLogger);

        $this->requestLogger
            ->expects(static::once())
            ->method('handle');

        $this->requestLogger
            ->expects(static::never())
            ->method('setUser');

        $this->requestLogger
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $this->requestLogger,
            $this->userRepository,
            $this->logger,
            $this->userTypeIdentification,
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

        $this->userRepository
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $this->userTypeIdentification
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $this->requestLogger
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($this->requestLogger);

        $this->requestLogger
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $this->requestLogger,
            $this->userRepository,
            $this->logger,
            $this->userTypeIdentification,
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

        $this->userRepository
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn(null);

        $this->userTypeIdentification
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($securityUser);

        $this->requestLogger
            ->expects(static::never())
            ->method('setUser');

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf('User not found for UUID: "%s".', $user->getId()),
                RequestLogSubscriber::getSubscribedEvents()
            );

        (new RequestLogSubscriber(
            $this->requestLogger,
            $this->userRepository,
            $this->logger,
            $this->userTypeIdentification,
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

        /**
         * @var MockObject|ApiKeyUser $apiKeyUser
         */
        $apiKeyUser = $this->getMockBuilder(ApiKeyUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKey = new ApiKey();

        $apiKeyUser
            ->expects(static::once())
            ->method('getApiKey')
            ->willReturn($apiKey);

        $this->userTypeIdentification
            ->expects(static::once())
            ->method('getIdentity')
            ->willReturn($apiKeyUser);

        $this->requestLogger
            ->expects(static::once())
            ->method('setApiKey')
            ->with($apiKey)
            ->willReturn($this->requestLogger);

        $this->requestLogger
            ->expects(static::never())
            ->method('setUser');

        (new RequestLogSubscriber(
            $this->requestLogger,
            $this->userRepository,
            $this->logger,
            $this->userTypeIdentification,
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
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS', 'REQUEST_URI' => '/foobar']);
        $response = new Response();

        $event = new TerminateEvent(static::$kernel, $request, $response);

        $this->requestLogger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($this->requestLogger);

        (new RequestLogSubscriber(
            $this->requestLogger,
            $this->userRepository,
            $this->logger,
            $this->userTypeIdentification,
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

        $this->requestLogger
            ->expects(static::never())
            ->method('setRequest');

        $this->requestLogger
            ->expects(static::never())
            ->method('setResponse');

        $this->userTypeIdentification
            ->expects(static::never())
            ->method('getIdentity');

        $this->requestLogger
            ->expects(static::never())
            ->method('setMasterRequest');

        $this->requestLogger
            ->expects(static::never())
            ->method('handle');

        $this->requestLogger
            ->expects(static::never())
            ->method('setUser');

        $this->requestLogger
            ->expects(static::never())
            ->method('setApiKey');

        (new RequestLogSubscriber(
            $this->requestLogger,
            $this->userRepository,
            $this->logger,
            $this->userTypeIdentification,
            [$ignored]
        ))
            ->onTerminateEvent($event);
    }

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
}
