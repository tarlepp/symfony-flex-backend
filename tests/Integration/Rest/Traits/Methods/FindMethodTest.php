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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use function assert;

/**
 * Class FindMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindMethodTest extends KernelTestCase
{
    private MockObject | RestResourceInterface | null $resource = null;
    private MockObject | ResponseHandlerInterface | null $responseHandler = null;
    private MockObject | FindMethodTestClass | null $validTestClass = null;
    private MockObject | FindMethodInvalidTestClass | null $inValidTestClass = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $this->responseHandler = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validTestClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$this->resource, $this->responseHandler]
        );

        $this->inValidTestClass = $this->getMockForAbstractClass(FindMethodInvalidTestClass::class);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /* @codingStandardsIgnoreEnd */

        $this->getInValidTestClass()->findMethod(Request::create('/'));
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
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->getValidTestClass()->findMethod(Request::create('/', $httpMethod))->getContent();
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
        $request = Request::create('/');

        $this->getResourceMock()
            ->expects(static::once())
            ->method('find')
            ->with([], [], null, null, [])
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $this->getValidTestClass()->findMethod($request);
    }

    /**
     * @dataProvider dataProviderTestThatTraitCallsServiceMethods
     *
     * @phpstan-param StringableArrayObject<array<mixed>> $criteria
     * @phpstan-param StringableArrayObject<array<mixed>> $orderBy
     * @phpstan-param StringableArrayObject<array<mixed>> $search
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
        $request = Request::create('/' . $queryString);

        $this->getResourceMock()
            ->expects(static::once())
            ->method('find')
            ->with(
                $criteria->getArrayCopy(),
                $orderBy->getArrayCopy(),
                $limit,
                $offset,
                $search->getArrayCopy()
            )
            ->willReturn([]);

        $this->getResponseHandlerMock()
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, [], $this->resource);

        $this->getValidTestClass()->findMethod($request);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findMethod` throws an exception when `?where` parameter is not valid JSON
     */
    public function testThatTraitThrowsAnExceptionWhenWhereParameterIsNotValidJson(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $this->getValidTestClass()->findMethod(Request::create('/?where=foo'));
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
     * @return Generator<array{0: string, 1: StringableArrayObject, 2: StringableArrayObject}>
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
            new StringableArrayObject(['foo' => 'bar']),
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
            new StringableArrayObject(['column' => 'ASC']),
            null,
            null,
            new StringableArrayObject([]),
        ];

        yield [
            '?order=-column',
            new StringableArrayObject([]),
            new StringableArrayObject(['column' => 'DESC']),
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

    private function getValidTestClass(): FindMethodTestClass
    {
        assert($this->validTestClass instanceof FindMethodTestClass);

        return $this->validTestClass;
    }

    private function getInValidTestClass(): FindMethodInvalidTestClass
    {
        assert($this->inValidTestClass instanceof FindMethodInvalidTestClass);

        return $this->inValidTestClass;
    }

    private function getResourceMock(): MockObject
    {
        assert($this->resource instanceof MockObject);

        return $this->resource;
    }

    private function getResponseHandlerMock(): MockObject
    {
        assert($this->responseHandler instanceof MockObject);

        return $this->responseHandler;
    }
}
