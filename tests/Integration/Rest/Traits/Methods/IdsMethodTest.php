<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\IdsMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\IdsMethodTestClass;
use App\Tests\Utils\StringableArrayObject;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
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
class IdsMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that `idsMethod` throws an exception if class doesn't implement `ControllerInterface`")]
    public function testThatTraitThrowsAnException(): void
    {
        $regex = '/You cannot use (.*) controller class with REST traits if that does not implement ' .
            '(.*)ControllerInterface\'/';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches($regex);

        new IdsMethodInvalidTestClass()
            ->idsMethod(Request::create('/'));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod')]
    #[TestDox('Test that `idsMethod` throws an exception when using `$httpMethod` HTTP method')]
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        new IdsMethodTestClass($resourceMock, $responseHandlerMock)
            ->idsMethod(Request::create('/', $httpMethod))
            ->getContent();
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitHandlesException')]
    #[TestDox('Test that `patchMethod` uses `$expectedCode` HTTP status code with `$exception` exception')]
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceMock
            ->expects($this->once())
            ->method('getIds')
            ->with([], [])
            ->willThrowException($exception);

        new IdsMethodTestClass($resourceMock, $responseHandlerMock)
            ->idsMethod(Request::create('/'));
    }

    /**
     * @phpstan-param StringableArrayObject<mixed> $criteria
     * @phpstan-param StringableArrayObject<mixed> $search
     * @psalm-param StringableArrayObject $criteria
     * @psalm-param StringableArrayObject $search
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitCallsServiceMethods')]
    #[TestDox('Test that `idsMethod` method calls expected service methods when using `$queryString` as query string')]
    public function testThatTraitCallsServiceMethods(
        string $queryString,
        StringableArrayObject $criteria,
        StringableArrayObject $search
    ): void {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = Request::create('/' . $queryString);

        $resourceMock
            ->expects($this->once())
            ->method('getIds')
            ->with($criteria->getArrayCopy(), $search->getArrayCopy())
            ->willReturn([]);

        $responseHandlerMock
            ->expects($this->once())
            ->method('createResponse')
            ->with($request, [], $resourceMock);

        new IdsMethodTestClass($resourceMock, $responseHandlerMock)
            ->idsMethod($request);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `idsMethod` throws an exception when `?where` parameter is not valid JSON')]
    public function testThatTraitThrowsAnExceptionWhenWhereParameterIsNotValidJson(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        new IdsMethodTestClass($resourceMock, $responseHandlerMock)
            ->idsMethod(Request::create('/?where=foo'));
    }

    /**
     * @psalm-return Generator<array{0: string, 1: StringableArrayObject, 2: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>, 2: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatTraitCallsServiceMethods(): Generator
    {
        yield ['', new StringableArrayObject([]), new StringableArrayObject([])];

        yield [
            '?where={"foo": "bar"}',
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
            new StringableArrayObject([]),
        ];

        yield [
            '?where={"foo": {"bar": "foobar"}}',
            new StringableArrayObject([
                'foo' => [
                    'bar' => 'foobar',
                ],
            ]),
            new StringableArrayObject([]),
        ];

        yield [
            '?search=term',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'or' => ['term'],
            ]),
        ];

        yield [
            '?search=term1+term2',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'or' => ['term1', 'term2'],
            ]),
        ];

        yield [
            '?search={"and": ["term1", "term2"]}',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'and' => ['term1', 'term2'],
            ]),
        ];

        yield [
            '?search={"or": ["term1", "term2"]}',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'or' => ['term1', 'term2'],
            ]),
        ];

        yield [
            '?search={"and": ["term1", "term2"], "or": ["term3", "term4"]}',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'and' => ['term1', 'term2'],
                'or' => ['term3', 'term4'],
            ]),
        ];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield [Request::METHOD_HEAD];
        yield [Request::METHOD_PATCH];
        yield [Request::METHOD_POST];
        yield [Request::METHOD_PUT];
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
