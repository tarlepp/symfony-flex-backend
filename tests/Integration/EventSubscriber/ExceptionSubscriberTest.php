<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/ExceptionSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use App\Utils\JSON;
use App\Utils\Tests\PhpUnitUtil;
use BadMethodCallException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Exception;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;
use function array_keys;

/**
 * Class ExceptionSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ExceptionSubscriberTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderEnvironment
     *
     * @param string $environment
     */
    public function testThatOnKernelExceptionMethodCallsLogger(string $environment): void
    {
        /**
         * @var MockObject|TokenStorageInterface $stubTokenStorage
         * @var MockObject|LoggerInterface       $stubLogger
         * @var MockObject|ExceptionEvent        $stubEvent
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubEvent = $this->createMock(ExceptionEvent::class);

        $exception = new Exception('test exception');

        $stubEvent
            ->expects(static::once())
            ->method('getThrowable')
            ->willReturn($exception);

        $stubLogger
            ->expects(static::once())
            ->method('error')
            ->with((string)$exception);

        $subscriber = new ExceptionSubscriber($stubTokenStorage, $environment);
        $subscriber->setLogger($stubLogger);
        $subscriber->onKernelException($stubEvent);

        unset($subscriber, $stubLogger, $stubEvent, $exception, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderEnvironment
     *
     * @param string $environment
     */
    public function testThatOnKernelExceptionMethodSetResponse(string $environment): void
    {
        /**
         * @var MockObject|TokenStorageInterface $stubTokenStorage
         * @var MockObject|LoggerInterface       $stubLogger
         * @var MockObject|ExceptionEvent        $stubEvent
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubEvent = $this->createMock(ExceptionEvent::class);

        $exception = new Exception('test exception');

        $stubEvent
            ->expects(static::once())
            ->method('getThrowable')
            ->willReturn($exception);

        $stubEvent
            ->expects(static::once())
            ->method('setResponse');

        $subscriber = new ExceptionSubscriber($stubTokenStorage, $environment);
        $subscriber->setLogger($stubLogger);
        $subscriber->onKernelException($stubEvent);

        unset($subscriber, $stubEvent, $exception, $stubLogger, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderTestResponseHasExpectedStatusCode
     *
     * @param int       $expectedStatus
     * @param Exception $exception
     * @param string    $environment
     */
    public function testResponseHasExpectedStatusCode(
        int $expectedStatus,
        Exception $exception,
        string $environment
    ): void {
        /**
         * @var MockObject|TokenStorageInterface $stubTokenStorage
         * @var MockObject|LoggerInterface       $stubLogger
         * @var MockObject|HttpKernelInterface   $stubHttpKernel
         * @var MockObject|Request               $stubRequest
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubHttpKernel = $this->createMock(HttpKernelInterface::class);
        $stubRequest = $this->createMock(Request::class);

        // Create event
        $event = new ExceptionEvent(
            $stubHttpKernel,
            $stubRequest,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $subscriber = new ExceptionSubscriber($stubTokenStorage, $environment);
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
     * @throws Throwable
     */
    public function testThatResponseHasExpectedKeys(array $expectedKeys, string $environment): void
    {
        /**
         * @var MockObject|TokenStorageInterface $stubTokenStorage
         * @var MockObject|LoggerInterface       $stubLogger
         * @var MockObject|HttpKernelInterface   $stubHttpKernel
         * @var MockObject|Request               $stubRequest
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubHttpKernel = $this->createMock(HttpKernelInterface::class);
        $stubRequest = $this->createMock(Request::class);

        // Create event
        $event = new ExceptionEvent(
            $stubHttpKernel,
            $stubRequest,
            HttpKernelInterface::MASTER_REQUEST,
            new Exception('error')
        );

        // Process event
        $subscriber = new ExceptionSubscriber($stubTokenStorage, $environment);
        $subscriber->setLogger($stubLogger);

        $subscriber->onKernelException($event);

        $result = JSON::decode($event->getResponse()->getContent(), true);

        static::assertSame($expectedKeys, array_keys($result));

        unset($result, $subscriber, $event, $stubRequest, $stubHttpKernel, $stubLogger, $stubTokenStorage);
    }

    /**
     * @dataProvider dataProviderTestThatGetStatusCodeReturnsExpected
     *
     * @param int       $expectedStatusCode
     * @param Exception $exception
     * @param bool      $user
     * @param string    $environment
     *
     * @throws Throwable
     */
    public function testThatGetStatusCodeReturnsExpected(
        int $expectedStatusCode,
        Exception $exception,
        bool $user,
        string $environment
    ): void {
        /**
         * @var MockObject|TokenStorageInterface  $stubTokenStorage
         * @var MockObject|LoggerInterface        $stubLogger
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        if ($user) {
            $stubTokenStorage
                ->expects(static::once())
                ->method('getToken')
                ->willReturn(true);
        }

        $subscriber = new ExceptionSubscriber($stubTokenStorage, $environment);
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
     * @param string    $expectedMessage
     * @param Exception $exception
     * @param string    $environment
     *
     * @throws Throwable
     */
    public function testThatGetExceptionMessageReturnsExpected(
        string $expectedMessage,
        Exception $exception,
        string $environment
    ): void {
        /**
         * @var MockObject|TokenStorageInterface $stubTokenStorage
         * @var MockObject|LoggerInterface       $stubLogger
         */
        $stubTokenStorage = $this->createMock(TokenStorageInterface::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        // Create subscriber
        $subscriber = new ExceptionSubscriber($stubTokenStorage, $environment);
        $subscriber->setLogger($stubLogger);

        static::assertSame(
            $expectedMessage,
            PhpUnitUtil::callMethod($subscriber, 'getExceptionMessage', [$exception])
        );

        unset($subscriber, $stubLogger, $stubTokenStorage);
    }

    /**
     * @return Generator
     */
    public function dataProviderEnvironment(): Generator
    {
        yield ['dev'];

        yield ['prod'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestResponseHasExpectedStatusCode(): Generator
    {
        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new Exception(Exception::class),
            'dev',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new Exception(Exception::class),
            'prod',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new BadMethodCallException(BadMethodCallException::class),
            'dev',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new BadMethodCallException(BadMethodCallException::class),
            'prod',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AuthenticationException(AuthenticationException::class),
            'dev',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AuthenticationException(AuthenticationException::class),
            'prod',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AccessDeniedException(AccessDeniedException::class),
            'dev',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AccessDeniedException(AccessDeniedException::class),
            'prod',
        ];

        yield [
            Response::HTTP_BAD_REQUEST,
            new HttpException(Response::HTTP_BAD_REQUEST, HttpException::class),
            'dev',
        ];

        yield [
            Response::HTTP_BAD_REQUEST,
            new HttpException(Response::HTTP_BAD_REQUEST, HttpException::class),
            'prod',
        ];

        yield [
            Response::HTTP_I_AM_A_TEAPOT,
            new HttpException(Response::HTTP_I_AM_A_TEAPOT, HttpException::class),
            'dev',
        ];

        yield [
            Response::HTTP_I_AM_A_TEAPOT,
            new HttpException(Response::HTTP_I_AM_A_TEAPOT, HttpException::class),
            'prod',
        ];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatResponseHasExpectedKeys(): Generator
    {
        yield [
            ['message', 'code', 'status'],
            'prod',
        ];

        yield [
            ['message', 'code', 'status', 'debug'],
            'dev',
        ];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetStatusCodeReturnsExpected(): Generator
    {
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, new Exception(), false, 'dev'];

        yield [Response::HTTP_INTERNAL_SERVER_ERROR, new Exception(), false, 'prod'];

        yield [Response::HTTP_UNAUTHORIZED, new AuthenticationException(), false, 'dev'];

        yield [Response::HTTP_UNAUTHORIZED, new AuthenticationException(), false, 'prod'];

        yield [Response::HTTP_UNAUTHORIZED, new AccessDeniedException(), false, 'dev'];

        yield [Response::HTTP_UNAUTHORIZED, new AccessDeniedException(), false, 'prod'];

        yield [Response::HTTP_FORBIDDEN, new AccessDeniedException(), true, 'dev'];

        yield [Response::HTTP_FORBIDDEN, new AccessDeniedException(), true, 'prod'];

        yield [Response::HTTP_NOT_FOUND, new NotFoundHttpException(), false, 'dev'];

        yield [Response::HTTP_NOT_FOUND, new NotFoundHttpException(), false, 'prod'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetExceptionMessageReturnsExpected(): Generator
    {
        yield [
            'test',
            new Exception('test'),
            'prod',
        ];

        yield [
            'test',
            new Exception('test'),
            'dev',
        ];

        yield [
            'Access denied.',
            new AccessDeniedHttpException('some message'),
            'prod',
        ];

        yield [
            'some message',
            new AccessDeniedHttpException('some message'),
            'dev',
        ];

        yield [
            'Access denied.',
            new AccessDeniedException('some message'),
            'prod',
        ];

        yield [
            'some message',
            new AccessDeniedException('some message'),
            'dev',
        ];

        yield [
            'Database error.',
            new DBALException('some message'),
            'prod',
        ];

        yield [
            'some message',
            new DBALException('some message'),
            'dev',
        ];

        yield [
            'Database error.',
            new ORMException('some message'),
            'prod',
        ];

        yield [
            'some message',
            new ORMException('some message'),
            'dev',
        ];
    }
}
