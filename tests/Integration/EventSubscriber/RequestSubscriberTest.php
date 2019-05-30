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
use App\Security\ApiKeyUser;
use App\Utils\RequestLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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

        $request = new Request();
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger $logger
         * @var MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('setMasterRequest')
            ->with($event->isMasterRequest())
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('handle');

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);

        unset($subscriber, $logger, $tokenStorage, $logger, $event, $response, $request);
    }

    /**
     * @throws Throwable
     */
    public function testThatSetUserIsCalled(): void
    {
        static::bootKernel();

        $request = new Request();
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger $logger
         * @var MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $user = new User();

        $token
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        $logger
            ->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturn($logger);

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);

        unset($subscriber, $logger, $tokenStorage, $token, $user, $event, $response, $request);
    }

    /**
     * @throws Throwable
     */
    public function testThatSetApiKeyIsCalled(): void
    {
        static::bootKernel();

        $request = new Request();
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger $logger
         * @var MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
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

        $logger
            ->expects(static::once())
            ->method('setApiKey')
            ->with($apiKey)
            ->willReturn($logger);

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);

        unset($subscriber, $logger, $tokenStorage, $token, $user, $apiKey, $event, $response, $request);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoggerServiceIsNotCalledIfOptionsRequest(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS']);
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger $logger
         * @var MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $logger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($logger);

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);

        unset($subscriber, $logger, $tokenStorage, $event, $response, $request);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoggerServiceIsNotCalledIfHealthzRequest(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/healthz']);
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var MockObject|RequestLogger $logger
         * @var MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $logger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($logger);

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);

        unset($subscriber, $logger, $tokenStorage, $event, $response, $request);
    }
}
