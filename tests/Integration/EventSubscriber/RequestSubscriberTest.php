<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/RequestSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\ApiKey;
use App\Entity\User;
use App\EventSubscriber\RequestSubscriber;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use App\Utils\RequestLogger;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Throwable;

/**
 * Class RequestSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatMethodCallsExpectedLoggerMethods(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger         $requestLogger
         * @var MockObject|TokenStorageInterface $tokenStorage
         * @var MockObject|UserRepository        $userRepository
         * @var MockObject|LoggerInterface       $logger
         */
        $requestLogger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $requestLogger
            ->expects(static::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLogger);

        $requestLogger
            ->expects(static::once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($requestLogger);

        $requestLogger
            ->expects(static::once())
            ->method('setMasterRequest')
            ->with($event->isMasterRequest())
            ->willReturn($requestLogger);

        $requestLogger
            ->expects(static::once())
            ->method('handle');

        $subscriber = new RequestSubscriber($requestLogger, $userRepository, $tokenStorage, $logger);
        $subscriber->onKernelResponse($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatSetUserIsCalled(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger         $requestLogger
         * @var MockObject|TokenStorageInterface $tokenStorage
         * @var MockObject|UserRepository        $userRepository
         * @var MockObject|LoggerInterface       $logger
         */
        $requestLogger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $user = (new User())->setUsername('test user');

        $securityUser = new SecurityUser($user);

        $userRepository
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $token
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($securityUser);

        $tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        $requestLogger
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($requestLogger);

        $subscriber = new RequestSubscriber($requestLogger, $userRepository, $tokenStorage, $logger);
        $subscriber->onKernelResponse($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoggerIsCalledIfUserIsNotFoundByRepository(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger         $requestLogger
         * @var MockObject|TokenStorageInterface $tokenStorage
         * @var MockObject|UserRepository        $userRepository
         * @var MockObject|LoggerInterface       $logger
         */
        $requestLogger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $user = (new User())->setUsername('test user');

        $securityUser = new SecurityUser($user);

        $userRepository
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn(null);

        $token
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($securityUser);

        $tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        $requestLogger
            ->expects(static::never())
            ->method('setUser');

        $logger
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf('User not found for UUID: "%s".', $user->getId()),
                RequestSubscriber::getSubscribedEvents()
            );

        $subscriber = new RequestSubscriber($requestLogger, $userRepository, $tokenStorage, $logger);
        $subscriber->onKernelResponse($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatSetApiKeyIsCalled(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/foobar']);
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger         $requestLogger
         * @var MockObject|TokenStorageInterface $tokenStorage
         * @var MockObject|UserRepository        $userRepository
         * @var MockObject|LoggerInterface       $logger
         */
        $requestLogger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $user = $this->getMockBuilder(ApiKeyUser::class)->disableOriginalConstructor()->getMock();

        $apiKey = new ApiKey();

        $user
            ->expects(static::once())
            ->method('getApiKey')
            ->willReturn($apiKey);

        $token
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        $requestLogger
            ->expects(static::once())
            ->method('setApiKey')
            ->with($apiKey)
            ->willReturn($requestLogger);

        $subscriber = new RequestSubscriber($requestLogger, $userRepository, $tokenStorage, $logger);
        $subscriber->onKernelResponse($event);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoggerServiceIsNotCalledIfOptionsRequest(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS', 'REQUEST_URI' => '/foobar']);
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger         $requestLogger
         * @var MockObject|TokenStorageInterface $tokenStorage
         * @var MockObject|UserRepository        $userRepository
         * @var MockObject|LoggerInterface       $logger
         */
        $requestLogger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $requestLogger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLogger);

        $subscriber = new RequestSubscriber($requestLogger, $userRepository, $tokenStorage, $logger);
        $subscriber->onKernelResponse($event);
    }

    /**
     * @dataProvider dataProviderTestThatLoggerServiceIsNotCalledWhenUsingWhitelistedUrl
     *
     * @param string $url
     *
     * @testdox Test that `Logger` service is not called when making request to `$url` url.
     *
     * @throws Throwable
     */
    public function testThatLoggerServiceIsNotCalledWhenUsingWhitelistedUrl(string $url): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $url]);
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger         $requestLogger
         * @var MockObject|TokenStorageInterface $tokenStorage
         * @var MockObject|UserRepository        $userRepository
         * @var MockObject|LoggerInterface       $logger
         */
        $requestLogger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $requestLogger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($requestLogger);

        $subscriber = new RequestSubscriber($requestLogger, $userRepository, $tokenStorage, $logger);
        $subscriber->onKernelResponse($event);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatLoggerServiceIsNotCalledWhenUsingWhitelistedUrl(): Generator
    {
        yield [''];
        yield ['/'];
        yield ['/healthz'];
        yield ['/version'];
    }
}
