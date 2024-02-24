<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/CountMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\CountMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\CountMethodTestClass;
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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CountMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that `countMethod` throws an exception if class doesn't implement `ControllerInterface`")]
    public function testThatTraitThrowsAnException(): void
    {
        $inValidTestClassMock = $this->getMockForAbstractClass(CountMethodInvalidTestClass::class);

        $this->expectException(LogicException::class);

        $regex = '/You cannot use (.*) controller class with REST traits if that does not implement ' .
            '(.*)ControllerInterface\'/';

        $this->expectExceptionMessageMatches($regex);

        $inValidTestClassMock->countMethod(Request::create('/'));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod')]
    #[TestDox('Test that `countMethod` throws an exception when using `$httpMethod` HTTP method')]
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $this->expectException(MethodNotAllowedHttpException::class);

        $validTestClassMock->countMethod(Request::create('/', $httpMethod))->getContent();
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitHandlesException')]
    #[TestDox('Test that `countMethod` uses `$expectedCode` HTTP status code with `$exception` exception')]
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $request = Request::create('/');

        $resourceMock
            ->expects(self::once())
            ->method('count')
            ->with([], [])
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $validTestClassMock->countMethod($request);
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
    #[TestDox(
        'Test that `countMethod` method calls expected service methods when using `$queryString` as query string'
    )]
    public function testThatTraitCallsServiceMethods(
        string $queryString,
        StringableArrayObject $criteria,
        StringableArrayObject $search
    ): void {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $request = Request::create('/' . $queryString);

        $resourceMock
            ->expects(self::once())
            ->method('count')
            ->with($criteria->getArrayCopy(), $search->getArrayCopy())
            ->willReturn(0);

        $responseHandlerMock
            ->expects(self::once())
            ->method('createResponse')
            ->with(
                $request,
                [
                    'count' => 0,
                ],
                $resourceMock
            );

        $validTestClassMock->countMethod($request);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `countMethod` throws an exception when `?where` parameter is not valid JSON')]
    public function testThatTraitThrowsAnExceptionWhenWhereParameterIsNotValidJson(): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $validTestClassMock->countMethod(Request::create('/?where=foo'));
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
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
    public static function dataProviderTestThatTraitHandlesException(): Generator
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
}
