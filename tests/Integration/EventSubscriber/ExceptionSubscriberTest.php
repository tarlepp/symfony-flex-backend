<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/ExceptionSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use App\Utils\JSON;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class ExceptionSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ExceptionSubscriberTest extends KernelTestCase
{
    public function testThatOnKernelExceptionMethodCallsLogger(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface          $stubTokenStorage
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface                $stubLogger
         * @var \PHPUnit_Framework_MockObject_MockObject|GetResponseForExceptionEvent   $stubEvent
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubEvent = $this->createMock(GetResponseForExceptionEvent::class);

        $exception = new \Exception('test exception');

        $stubEvent
            ->expects(static::once())
            ->method('getException')
            ->willReturn($exception);

        $stubLogger
            ->expects(static::once())
            ->method('error')
            ->with((string)$exception);

        $subscriber = new ExceptionSubscriber($stubTokenStorage);
        $subscriber->setLogger($stubLogger);
        $subscriber->onKernelException($stubEvent);

        unset($subscriber, $stubLogger, $stubEvent, $exception, $stubTokenStorage);
    }

    public function testThatOnKernelExceptionMethodSetResponse(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface          $stubTokenStorage
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface                $stubLogger
         * @var \PHPUnit_Framework_MockObject_MockObject|GetResponseForExceptionEvent   $stubEvent
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubEvent = $this->createMock(GetResponseForExceptionEvent::class);

        $exception = new \Exception('test exception');

        $stubEvent
            ->expects(static::once())
            ->method('getException')
            ->willReturn($exception);

        $stubEvent
            ->expects(static::once())
            ->method('setResponse');

        $subscriber = new ExceptionSubscriber($stubTokenStorage);
        $subscriber->setLogger($stubLogger);
        $subscriber->onKernelException($stubEvent);

        unset($subscriber, $stubEvent, $exception, $stubLogger, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderTestResponseHasExpectedStatusCode
     *
     * @param int        $expectedStatus
     * @param \Exception $exception
     */
    public function testResponseHasExpectedStatusCode(int $expectedStatus, \Exception $exception): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface  $stubTokenStorage
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface        $stubLogger
         * @var \PHPUnit_Framework_MockObject_MockObject|HttpKernelInterface    $stubHttpKernel
         * @var \PHPUnit_Framework_MockObject_MockObject|Request                $stubRequest
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubHttpKernel = $this->createMock(HttpKernelInterface::class);
        $stubRequest = $this->createMock(Request::class);

        // Create event
        $event = new GetResponseForExceptionEvent(
            $stubHttpKernel,
            $stubRequest,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $subscriber = new ExceptionSubscriber($stubTokenStorage);
        $subscriber->setLogger($stubLogger);
        $subscriber->onKernelException($event);

        static::assertSame($expectedStatus, $event->getResponse()->getStatusCode());

        unset($subscriber, $event, $stubRequest, $stubHttpKernel, $stubLogger, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderTestThatResponseHasExpectedKeys
     *
     * @param array  $expectedKeys
     * @param string $environment
     *
     * @throws \ReflectionException
     */
    public function testThatResponseHasExpectedKeys(array $expectedKeys, string $environment): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface  $stubTokenStorage
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface        $stubLogger
         * @var \PHPUnit_Framework_MockObject_MockObject|HttpKernelInterface    $stubHttpKernel
         * @var \PHPUnit_Framework_MockObject_MockObject|Request                $stubRequest
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubHttpKernel = $this->createMock(HttpKernelInterface::class);
        $stubRequest = $this->createMock(Request::class);

        // Create event
        $event = new GetResponseForExceptionEvent(
            $stubHttpKernel,
            $stubRequest,
            HttpKernelInterface::MASTER_REQUEST,
            new \Exception('error')
        );

        // Process event
        $subscriber = new ExceptionSubscriber($stubTokenStorage);
        $subscriber->setLogger($stubLogger);

        PhpUnitUtil::setProperty('environment', $environment, $subscriber);

        $subscriber->onKernelException($event);

        $result = JSON::decode($event->getResponse()->getContent(), true);

        static::assertSame($expectedKeys, \array_keys($result));

        unset($result, $subscriber, $event, $stubRequest, $stubHttpKernel, $stubLogger, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderTestThatGetStatusCodeReturnsExpected
     *
     * @param int        $expectedStatusCode
     * @param \Exception $exception
     * @param bool       $user
     *
     * @throws \ReflectionException
     */
    public function testThatGetStatusCodeReturnsExpected(
        int $expectedStatusCode,
        \Exception $exception,
        bool $user
    ): void {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface  $stubTokenStorage
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface        $stubLogger
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        if ($user) {
            $stubTokenStorage
                ->expects(static::once())
                ->method('getToken')
                ->willReturn(true);
        }

        $subscriber = new ExceptionSubscriber($stubTokenStorage);
        $subscriber->setLogger($stubLogger);

        static::assertSame(
            $expectedStatusCode,
            PhpUnitUtil::callMethod($subscriber, 'getStatusCode', [$exception])
        );

        unset($subscriber, $stubLogger, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderTestThatGetExceptionMessageReturnsExpected
     *
     * @param string     $expectedMessage
     * @param \Exception $exception
     * @param string     $environment
     *
     * @throws \ReflectionException
     */
    public function testThatGetExceptionMessageReturnsExpected(
        string $expectedMessage,
        \Exception $exception,
        string $environment
    ): void {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface  $stubTokenStorage
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface        $stubLogger
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        // Create subscriber
        $subscriber = new ExceptionSubscriber($stubTokenStorage);
        $subscriber->setLogger($stubLogger);

        PhpUnitUtil::setProperty('environment', $environment, $subscriber);

        static::assertSame(
            $expectedMessage,
            PhpUnitUtil::callMethod($subscriber, 'getExceptionMessage', [$exception])
        );

        unset($subscriber, $stubLogger, $stubTokenStorage);
    }

    /**
     * @return array
     */
    public function dataProviderTestResponseHasExpectedStatusCode(): array
    {
        return [
            [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                new \Exception(\Exception::class),
            ],
            [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                new \BadMethodCallException(\BadMethodCallException::class),
            ],
            [
                Response::HTTP_UNAUTHORIZED,
                new AuthenticationException(AuthenticationException::class),
            ],
            [
                Response::HTTP_UNAUTHORIZED,
                new AccessDeniedException(AccessDeniedException::class),
            ],
            [
                Response::HTTP_BAD_REQUEST,
                new HttpException(Response::HTTP_BAD_REQUEST, HttpException::class),
            ],
            [
                Response::HTTP_I_AM_A_TEAPOT,
                new HttpException(Response::HTTP_I_AM_A_TEAPOT, HttpException::class),
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatResponseHasExpectedKeys(): array
    {
        return [
            [
                ['message', 'code', 'status'],
                'prod',
            ],
            [
                ['message', 'code', 'status', 'debug'],
                'dev',
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetStatusCodeReturnsExpected(): array
    {
        return [
            [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                new \Exception(),
                false,
            ],
            [
                Response::HTTP_UNAUTHORIZED,
                new AuthenticationException(),
                false,
            ],
            [
                Response::HTTP_UNAUTHORIZED,
                new AccessDeniedException(),
                false,
            ],
            [
                Response::HTTP_FORBIDDEN,
                new AccessDeniedException(),
                true,
            ],
            [
                Response::HTTP_NOT_FOUND,
                new NotFoundHttpException(),
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetExceptionMessageReturnsExpected(): array
    {
        return [
            [
                'test',
                new \Exception('test'),
                'prod',
            ],
            [
                'test',
                new \Exception('test'),
                'dev',
            ],
            [
                'Access denied.',
                new AccessDeniedHttpException('some message'),
                'prod',
            ],
            [
                'some message',
                new AccessDeniedHttpException('some message'),
                'dev',
            ],
            [
                'Access denied.',
                new AccessDeniedException('some message'),
                'prod',
            ],
            [
                'some message',
                new AccessDeniedException('some message'),
                'dev',
            ],
            [
                'Database error.',
                new DBALException('some message'),
                'prod',
            ],
            [
                'some message',
                new DBALException('some message'),
                'dev',
            ],
            [
                'Database error.',
                new ORMException('some message'),
                'prod',
            ],
            [
                'some message',
                new ORMException('some message'),
                'dev',
            ],
        ];
    }
}
