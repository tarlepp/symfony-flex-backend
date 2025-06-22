<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/UpdateMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\UpdateMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\UpdateMethodTestClass;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UpdateMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that `updateMethod` throws an exception if class doesn't implement `ControllerInterface`")]
    public function testThatTraitThrowsAnException(): void
    {
        $regex = '/You cannot use (.*) controller class with REST traits if that does not implement ' .
            '(.*)ControllerInterface\'/';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches($regex);

        new UpdateMethodInvalidTestClass()
            ->updateMethod(
                Request::create('/' . Uuid::uuid4()->toString(), Request::METHOD_PUT),
                $this->getMockBuilder(RestDtoInterface::class)->getMock(),
                'some-id',
            );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod')]
    #[TestDox('Test that `updateMethod` throws an exception when using `$httpMethod` HTTP method')]
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        new UpdateMethodTestClass($resourceMock, $responseHandlerMock)
            ->updateMethod(
                Request::create('/' . Uuid::uuid4()->toString(), $httpMethod),
                $restDtoMock,
                'some-id',
            )
            ->getContent();
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitHandlesException')]
    #[TestDox('Test that `updateMethod` uses `$expectedCode` HTTP status code with `$exception` exception')]
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $uuid = Uuid::uuid4()->toString();

        $resourceMock
            ->expects($this->once())
            ->method('update')
            ->with($uuid, $restDtoMock, true)
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        new UpdateMethodTestClass($resourceMock, $responseHandlerMock)
            ->updateMethod(Request::create('/' . $uuid, Request::METHOD_PUT), $restDtoMock, $uuid);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `updateMethod` method calls expected service methods')]
    public function testThatTraitCallsServiceMethods(): void
    {
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $entityMock = $this->getMockBuilder(EntityInterface::class)->getMock();

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, Request::METHOD_PUT);

        $resourceMock
            ->expects($this->once())
            ->method('update')
            ->with($uuid, $restDtoMock, true)
            ->willReturn($entityMock);

        $responseHandlerMock
            ->expects($this->once())
            ->method('createResponse')
            ->with($request, $entityMock, $resourceMock);

        new UpdateMethodTestClass($resourceMock, $responseHandlerMock)
            ->updateMethod($request, $restDtoMock, $uuid);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield [Request::METHOD_HEAD];
        yield [Request::METHOD_GET];
        yield [Request::METHOD_POST];
        yield [Request::METHOD_PATCH];
        yield [Request::METHOD_DELETE];
        yield [Request::METHOD_OPTIONS];
        yield [Request::METHOD_CONNECT];
    }

    /**
     * @return Generator<array{0: Throwable, 1: int}>
     */
    public static function dataProviderTestThatTraitHandlesException(): Generator
    {
        yield [
            new HttpException(Response::HTTP_BAD_REQUEST, code: Response::HTTP_BAD_REQUEST),
            Response::HTTP_BAD_REQUEST,
        ];

        yield [new NoResultException(), Response::HTTP_NOT_FOUND];
        yield [new NotFoundHttpException(), Response::HTTP_NOT_FOUND];
        yield [new NonUniqueResultException(), Response::HTTP_INTERNAL_SERVER_ERROR];
        yield [new Exception(), Response::HTTP_BAD_REQUEST];
        yield [new LogicException(), Response::HTTP_BAD_REQUEST];
        yield [new InvalidArgumentException(), Response::HTTP_BAD_REQUEST];
    }
}
