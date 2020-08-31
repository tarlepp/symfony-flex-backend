<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/ExceptionSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\ExceptionSubscriber;
use App\Exception\ValidatorException;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Utils\JSON;
use App\Utils\Tests\PhpUnitUtil;
use BadMethodCallException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Exception;
use Generator;
use JsonException;
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
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidatorException as BaseValidatorException;
use Throwable;
use function array_keys;

/**
 * Class ExceptionSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ExceptionSubscriberTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderEnvironment
     *
     * @throws JsonException
     *
     * @testdox Test that `onKernelException` method calls logger with environment: '$environment'.
     */
    public function testThatOnKernelExceptionMethodCallsLogger(string $environment): void
    {
        /**
         * @var MockObject|UserTypeIdentification $stubUserTypeIdentification
         * @var MockObject|LoggerInterface $stubLogger
         * @var MockObject|KernelInterface $stubKernel
         */
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubKernel = $this->createMock(KernelInterface::class);

        $exception = new Exception('test exception');
        $event = new ExceptionEvent($stubKernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $exception);

        $stubLogger
            ->expects(static::once())
            ->method('error')
            ->with((string)$exception);

        (new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment))
            ->onKernelException($event);
    }

    /**
     * @dataProvider dataProviderEnvironment
     *
     * @throws JsonException
     *
     * @testdox Test that `ExceptionEvent::setResponse` method is called with environment: '$environment'.
     */
    public function testThatOnKernelExceptionMethodSetResponse(string $environment): void
    {
        /**
         * @var MockObject|UserTypeIdentification $stubUserTypeIdentification
         * @var MockObject|LoggerInterface $stubLogger
         * @var MockObject|KernelInterface $stubKernel
         */
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubKernel = $this->createMock(KernelInterface::class);

        $exception = new Exception('test exception');
        $event = new ExceptionEvent($stubKernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $exception);

        $originalResponse = $event->getResponse();

        (new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment))
            ->onKernelException($event);

        static::assertNotSame($originalResponse, $event->getResponse());
    }

    /**
     * @dataProvider dataProviderTestResponseHasExpectedStatusCode
     *
     * @throws JsonException
     *
     * @testdox Test that `Response` has status code `$status` and message `$message` with environment: '$environment'.
     */
    public function testThatResponseHasExpectedStatusCode(
        int $status,
        Throwable $exception,
        string $environment,
        string $message
    ): void {
        /**
         * @var MockObject|UserTypeIdentification $stubUserTypeIdentification
         * @var MockObject|LoggerInterface $stubLogger
         * @var MockObject|HttpKernelInterface $stubHttpKernel
         * @var MockObject|Request $stubRequest
         */
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
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

        (new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment))
            ->onKernelException($event);

        static::assertSame($status, $event->getResponse()->getStatusCode());
        static::assertSame($message, JSON::decode($event->getResponse()->getContent())->message);
    }

    /**
     * @dataProvider dataProviderTestThatResponseHasExpectedKeys
     *
     * @throws Throwable
     *
     * @testdox Test that `Response` has expected keys in JSON response with environment: '$environment'.
     */
    public function testThatResponseHasExpectedKeys(array $expectedKeys, string $environment): void
    {
        /**
         * @var MockObject|UserTypeIdentification $stubUserTypeIdentification
         * @var MockObject|LoggerInterface $stubLogger
         * @var MockObject|HttpKernelInterface $stubHttpKernel
         * @var MockObject|Request $stubRequest
         */
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
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

        (new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment))
            ->onKernelException($event);

        $result = JSON::decode($event->getResponse()->getContent(), true);

        static::assertSame($expectedKeys, array_keys($result));
    }

    /**
     * @dataProvider dataProviderTestThatGetStatusCodeReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `getStatusCode` returns `$expectedStatusCode` with environment: '$environment'.
     */
    public function testThatGetStatusCodeReturnsExpected(
        int $expectedStatusCode,
        Throwable $exception,
        bool $user,
        string $environment
    ): void {
        /**
         * @var MockObject|UserTypeIdentification $stubUserTypeIdentification
         * @var MockObject|LoggerInterface $stubLogger
         */
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        if ($user) {
            $stubUserTypeIdentification
                ->expects(static::once())
                ->method('getSecurityUser')
                ->willReturn(new SecurityUser(new User()));
        }

        $subscriber = new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment);

        static::assertSame(
            $expectedStatusCode,
            PhpUnitUtil::callMethod($subscriber, 'getStatusCode', [$exception])
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetExceptionMessageReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `$environment` environment exception message is `$expectedMessage`.
     */
    public function testThatGetExceptionMessageReturnsExpected(
        string $expectedMessage,
        Throwable $exception,
        string $environment
    ): void {
        /**
         * @var MockObject|UserTypeIdentification $stubUserTypeIdentification
         * @var MockObject|LoggerInterface $stubLogger
         */
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        // Create subscriber
        $subscriber = new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment);

        static::assertSame(
            $expectedMessage,
            PhpUnitUtil::callMethod($subscriber, 'getExceptionMessage', [$exception])
        );
    }

    public function dataProviderEnvironment(): Generator
    {
        yield ['dev'];

        yield ['test'];

        yield ['prod'];
    }

    /**
     * @throws JsonException
     */
    public function dataProviderTestResponseHasExpectedStatusCode(): Generator
    {
        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new Exception(Throwable::class),
            'dev',
            Throwable::class,
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new Exception(Throwable::class),
            'prod',
            'Internal server error.',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new BadMethodCallException(BadMethodCallException::class),
            'dev',
            BadMethodCallException::class,
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new BadMethodCallException(BadMethodCallException::class),
            'prod',
            'Internal server error.',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AuthenticationException(AuthenticationException::class),
            'dev',
            AuthenticationException::class,
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AuthenticationException(AuthenticationException::class),
            'prod',
            'Access denied.',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AccessDeniedException(AccessDeniedException::class),
            'dev',
            AccessDeniedException::class,
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            new AccessDeniedException(AccessDeniedException::class),
            'prod',
            'Access denied.',
        ];

        yield [
            Response::HTTP_BAD_REQUEST,
            new HttpException(Response::HTTP_BAD_REQUEST, HttpException::class),
            'dev',
            HttpException::class,
        ];

        yield [
            Response::HTTP_BAD_REQUEST,
            new HttpException(Response::HTTP_BAD_REQUEST, HttpException::class),
            'prod',
            HttpException::class,
        ];

        yield [
            Response::HTTP_I_AM_A_TEAPOT,
            new HttpException(Response::HTTP_I_AM_A_TEAPOT, HttpException::class),
            'dev',
            HttpException::class,
        ];

        yield [
            Response::HTTP_I_AM_A_TEAPOT,
            new HttpException(Response::HTTP_I_AM_A_TEAPOT, HttpException::class),
            'prod',
            HttpException::class,
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new DBALException('Error message', Response::HTTP_INTERNAL_SERVER_ERROR),
            'dev',
            'Error message',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new DBALException('Error message', Response::HTTP_INTERNAL_SERVER_ERROR),
            'prod',
            'Database error.',
        ];

        $violation = new ConstraintViolation('some message', null, [], '', 'property', '', null, 'error-code');

        yield [
            Response::HTTP_BAD_REQUEST,
            new ValidatorException(User::class, new ConstraintViolationList([$violation])),
            'dev',
            '[{"message":"some message","propertyPath":"property","target":"App.Entity.User","code":"error-code"}]',
        ];

        yield [
            Response::HTTP_BAD_REQUEST,
            new ValidatorException(User::class, new ConstraintViolationList([$violation])),
            'prod',
            '[{"message":"some message","propertyPath":"property","target":"App.Entity.User","code":"error-code"}]',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new BaseValidatorException(User::class, 400),
            'prod',
            'Internal server error.',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new HttpException(0, 'message'),
            'dev',
            'message',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new HttpException(0, 'message'),
            'prod',
            'message',
        ];
    }

    public function dataProviderTestThatResponseHasExpectedKeys(): Generator
    {
        yield [
            ['message', 'code', 'status'],
            'prod',
        ];

        yield [
            ['message', 'code', 'status'],
            'test',
        ];

        yield [
            ['message', 'code', 'status', 'debug'],
            'dev',
        ];
    }

    /**
     * @throws JsonException
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

        $violation = new ConstraintViolation('some message', null, [], '', 'property', '', null, 'error-code');

        yield [
            Response::HTTP_BAD_REQUEST,
            new ValidatorException('', new ConstraintViolationList([$violation])),
            false,
            'dev',
        ];

        yield [
            Response::HTTP_BAD_REQUEST,
            new ValidatorException('', new ConstraintViolationList([$violation])),
            false,
            'prod',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new BaseValidatorException('', 400),
            false,
            'prod',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new HttpException(0, 'message'),
            false,
            'dev',
        ];

        yield [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new HttpException(0, 'message'),
            false,
            'prod',
        ];
    }

    /**
     * @throws JsonException
     */
    public function dataProviderTestThatGetExceptionMessageReturnsExpected(): Generator
    {
        yield [
            'Internal server error.',
            new Exception('test'),
            'prod',
        ];

        yield [
            'Internal server error.',
            new Exception('test', 433),
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

        yield [
            'some message',
            new NotFoundHttpException('some message'),
            'prod',
        ];

        yield [
            'some message',
            new NotFoundHttpException('some message'),
            'dev',
        ];

        $violation = new ConstraintViolation('some message', null, [], '', 'property', '', null, 'error-code');

        yield [
            '[{"message":"some message","propertyPath":"property","target":"App.Entity.User","code":"error-code"}]',
            new ValidatorException(User::class, new ConstraintViolationList([$violation])),
            'dev',
        ];

        yield [
            '[{"message":"some message","propertyPath":"property","target":"App.Entity.User","code":"error-code"}]',
            new ValidatorException(User::class, new ConstraintViolationList([$violation])),
            'prod',
        ];

        yield [
            'Internal server error.',
            new BaseValidatorException(User::class, 400),
            'prod',
        ];

        yield [
            'message',
            new HttpException(0, 'message'),
            'prod',
        ];

        yield [
            'message',
            new HttpException(0, 'message'),
            'dev',
        ];
    }
}
