<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\FindMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\FindMethodTestClass;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class FindMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `findMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $inValidTestClassMock = $this->getMockForAbstractClass(FindMethodInvalidTestClass::class);

        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /* @codingStandardsIgnoreEnd */

        $inValidTestClassMock->findMethod(Request::create('/'));
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `findMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $this->expectException(MethodNotAllowedHttpException::class);

        $validTestClassMock->findMethod(Request::create('/', $httpMethod))->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `findMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $request = Request::create('/');

        $resourceMock
            ->expects(self::once())
            ->method('find')
            ->with([], [], null, null, [])
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $validTestClassMock->findMethod($request);
    }

    /**
     * @dataProvider dataProviderTestThatTraitCallsServiceMethods
     *
     * @phpstan-param StringableArrayObject<mixed> $criteria
     * @phpstan-param StringableArrayObject<mixed> $orderBy
     * @phpstan-param StringableArrayObject<mixed> $search
     * @psalm-param StringableArrayObject $criteria
     * @psalm-param StringableArrayObject $orderBy
     * @psalm-param StringableArrayObject $search
     *
     * @throws Throwable
     *
     * @testdox Test that `findMethod` method calls expected service methods when using `$queryString` as query string
     */
    public function testThatTraitCallsServiceMethods(
        string $queryString,
        StringableArrayObject $criteria,
        StringableArrayObject $orderBy,
        ?int $limit,
        ?int $offset,
        StringableArrayObject $search
    ): void {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $request = Request::create('/' . $queryString);

        $resourceMock
            ->expects(self::once())
            ->method('find')
            ->with(
                $criteria->getArrayCopy(),
                $orderBy->getArrayCopy(),
                $limit,
                $offset,
                $search->getArrayCopy()
            )
            ->willReturn([]);

        $responseHandlerMock
            ->expects(self::once())
            ->method('createResponse')
            ->with($request, [], $resourceMock);

        $validTestClassMock->findMethod($request);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findMethod` throws an exception when `?where` parameter is not valid JSON
     */
    public function testThatTraitThrowsAnExceptionWhenWhereParameterIsNotValidJson(): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $validTestClassMock->findMethod(Request::create('/?where=foo'));
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['PATCH'];
        yield ['POST'];
        yield ['PUT'];
        yield ['DELETE'];
        yield ['OPTIONS'];
        yield ['CONNECT'];
        yield ['foobar'];
    }

    /**
     * @return Generator<array{0: Throwable, 1: int}>
     */
    public function dataProviderTestThatTraitHandlesException(): Generator
    {
        yield [new HttpException(400, '', null, [], 400), 400];
        yield [new NoResultException(), 404];
        yield [new NotFoundHttpException(), 404];
        yield [new NonUniqueResultException(), 500];
        yield [new Exception(), 400];
        yield [new LogicException(), 400];
        yield [new InvalidArgumentException(), 400];
    }

    /**
     * @psalm-return Generator<array{0: string, 1: StringableArrayObject, 2: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>, 2: StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatTraitCallsServiceMethods(): Generator
    {
        yield [
            '',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?where={"foo": "bar"}',
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
            new StringableArrayObject([]),
            null,
            null,
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
            null,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?search=term',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            null,
            new StringableArrayObject([
                'or' => ['term'],
            ]),
        ];

        yield [
            '?order=column',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'column' => 'ASC',
            ]),
            null,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?order=-column',
            new StringableArrayObject([]),
            new StringableArrayObject([
                'column' => 'DESC',
            ]),
            null,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?limit=10',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            10,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?limit=-10',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            10,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?offset=10',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            10,
            new StringableArrayObject([]),
        ];

        yield [
            '?offset=-10',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            10,
            new StringableArrayObject([]),
        ];

        yield [
            '?search=term1+term2',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            null,
            new StringableArrayObject([
                'or' => ['term1', 'term2'],
            ]),
        ];

        yield [
            '?search={"and": ["term1", "term2"]}',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            null,
            new StringableArrayObject([
                'and' => ['term1', 'term2'],
            ]),
        ];

        yield [
            '?search={"or": ["term1", "term2"]}',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            null,
            new StringableArrayObject([
                'or' => ['term1', 'term2'],
            ]),
        ];

        yield [
            '?search={"and": ["term1", "term2"], "or": ["term3", "term4"]}',
            new StringableArrayObject([]),
            new StringableArrayObject([]),
            null,
            null,
            new StringableArrayObject([
                'and' => ['term1', 'term2'],
                'or' => ['term3', 'term4'],
            ]),
        ];
    }
}
