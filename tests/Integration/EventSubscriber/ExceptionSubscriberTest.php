<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/ExceptionSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\ExceptionSubscriber;
use App\Exception\ValidatorException;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Tests\Utils\PhpUnitUtil;
use App\Utils\JSON;
use BadMethodCallException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
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
use function property_exists;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ExceptionSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderEnvironment')]
    #[TestDox("Test that `onKernelException` method calls logger with environment: '\$environment'.")]
    public function testThatOnKernelExceptionMethodCallsLogger(string $environment): void
    {
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubKernel = $this->createMock(KernelInterface::class);

        $exception = new Exception('test exception');
        $event = new ExceptionEvent($stubKernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $stubLogger
            ->expects($this->once())
            ->method('error')
            ->with((string)$exception);

        new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment)
            ->onKernelException($event);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderEnvironment')]
    #[TestDox('Test that `ExceptionEvent::setResponse` method is called with environment: `$environment`')]
    public function testThatOnKernelExceptionMethodSetResponse(string $environment): void
    {
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubKernel = $this->createMock(KernelInterface::class);

        $exception = new Exception('test exception');
        $event = new ExceptionEvent($stubKernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $originalResponse = $event->getResponse();

        new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment)
            ->onKernelException($event);

        self::assertNotSame($originalResponse, $event->getResponse());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestResponseHasExpectedStatusCode')]
    #[TestDox(
        'Test that `Response` has status code `$status` and message `$message` with environment: `$environment`'
    )]
    public function testThatResponseHasExpectedStatusCode(
        int $status,
        Throwable $exception,
        string $environment,
        string $message
    ): void {
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubHttpKernel = $this->createMock(HttpKernelInterface::class);
        $stubRequest = $this->createMock(Request::class);

        // Create event
        $event = new ExceptionEvent(
            $stubHttpKernel,
            $stubRequest,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment)
            ->onKernelException($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame($status, $response->getStatusCode());

        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertJson($content);

        $json = JSON::decode($content);

        self::assertIsObject($json);
        self::assertTrue(property_exists($json, 'message'));
        self::assertSame($message, $json->message);
    }

    /**
     * @param array<int, string> $expectedKeys
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatResponseHasExpectedKeys')]
    #[TestDox("Test that `Response` has expected keys in JSON response with environment: '\$environment'.")]
    public function testThatResponseHasExpectedKeys(array $expectedKeys, string $environment): void
    {
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);
        $stubHttpKernel = $this->createMock(HttpKernelInterface::class);
        $stubRequest = $this->createMock(Request::class);

        // Create event
        $event = new ExceptionEvent(
            $stubHttpKernel,
            $stubRequest,
            HttpKernelInterface::MAIN_REQUEST,
            new Exception('error')
        );

        new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment)
            ->onKernelException($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);

        $content = $response->getContent();

        self::assertNotFalse($content);

        $result = JSON::decode($content, true);

        self::assertIsArray($result);
        self::assertSame($expectedKeys, array_keys($result));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetStatusCodeReturnsExpected')]
    #[TestDox("Test that `getStatusCode` returns `\$expectedStatusCode` with environment: '\$environment'.")]
    public function testThatGetStatusCodeReturnsExpected(
        int $expectedStatusCode,
        Throwable $exception,
        bool $user,
        string $environment
    ): void {
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        if ($user) {
            $stubUserTypeIdentification
                ->expects($this->once())
                ->method('getSecurityUser')
                ->willReturn(new SecurityUser(new User()));
        }

        $subscriber = new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment);

        self::assertSame(
            $expectedStatusCode,
            PhpUnitUtil::callMethod($subscriber, 'getStatusCode', [$exception])
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetExceptionMessageReturnsExpected')]
    #[TestDox('Test that `$environment` environment exception message is `$expectedMessage`.')]
    public function testThatGetExceptionMessageReturnsExpected(
        string $expectedMessage,
        Throwable $exception,
        string $environment
    ): void {
        $stubUserTypeIdentification = $this->createMock(UserTypeIdentification::class);
        $stubLogger = $this->createMock(LoggerInterface::class);

        // Create subscriber
        $subscriber = new ExceptionSubscriber($stubLogger, $stubUserTypeIdentification, $environment);

        self::assertSame(
            $expectedMessage,
            PhpUnitUtil::callMethod($subscriber, 'getExceptionMessage', [$exception])
        );
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderEnvironment(): Generator
    {
        yield ['dev'];

        yield ['test'];

        yield ['prod'];
    }

    /**
     * @return Generator<array{0: int, 1: Throwable, 2: string, 3: string}>
     *
     * @throws JsonException
     */
    public static function dataProviderTestResponseHasExpectedStatusCode(): Generator
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

    /**
     * @return Generator<array{0: array<int, string>, 1: string}>
     */
    public static function dataProviderTestThatResponseHasExpectedKeys(): Generator
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
     * @return Generator<array{0: int, 1: Throwable, 2: boolean, 3: string}>
     *
     * @throws JsonException
     */
    public static function dataProviderTestThatGetStatusCodeReturnsExpected(): Generator
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
     * @return Generator<array{0: string, 1: Throwable, 2: string}>
     *
     * @throws JsonException
     */
    public static function dataProviderTestThatGetExceptionMessageReturnsExpected(): Generator
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
