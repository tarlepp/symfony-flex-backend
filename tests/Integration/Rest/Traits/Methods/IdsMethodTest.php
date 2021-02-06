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

/**
 * Class IdsMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IdsMethodTest extends KernelTestCase
{
    /**
     * @var MockObject|RestResourceInterface
     */
    private $resource;

    /**
     * @var MockObject|ResponseHandlerInterface
     */
    private $responseHandler;

    /**
     * @var MockObject|IdsMethodTestClass
     */
    private $validTestClass;

    /**
     * @var MockObject|IdsMethodInvalidTestClass
     */
    private $inValidTestClass;

    /**
     * @throws Throwable
     *
     * @testdox Test that `idsMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /** @codingStandardsIgnoreEnd */

        $this->inValidTestClass->idsMethod(Request::create('/'));
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `idsMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $request = Request::create('/', $httpMethod);

        $this->validTestClass->idsMethod($request)->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `patchMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $request = Request::create('/');

        $this->resource
            ->expects(static::once())
            ->method('getIds')
            ->with([], [])
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $this->validTestClass->idsMethod($request);
    }

    /**
     * @dataProvider dataProviderTestThatTraitCallsServiceMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `idsMethod` method calls expected service methods when using `$queryString` as query string
     */
    public function testThatTraitCallsServiceMethods(
        string $queryString,
        StringableArrayObject $criteria,
        StringableArrayObject $search
    ): void {
        $request = Request::create('/' . $queryString);

        $this->resource
            ->expects(static::once())
            ->method('getIds')
            ->with($criteria->getArrayCopy(), $search->getArrayCopy())
            ->willReturn([]);

        $this->responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, [], $this->resource);

        $this->validTestClass->idsMethod($request);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `idsMethod` throws an exception when `?where` parameter is not valid JSON
     */
    public function testThatTraitThrowsAnExceptionWhenWhereParameterIsNotValidJson(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $this->validTestClass->idsMethod(Request::create('/?where=foo'));
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatTraitCallsServiceMethods(): Generator
    {
        yield ['', new StringableArrayObject([]), new StringableArrayObject([])];

        yield [
            '?where={"foo": "bar"}',
            new StringableArrayObject(['foo' => 'bar']),
            new StringableArrayObject([]),
        ];

        yield [
            '?where={"foo": {"bar": "foobar"}}',
            new StringableArrayObject(['foo' => ['bar' => 'foobar']]),
            new StringableArrayObject([]),
        ];

        yield [
            '?search=term',
            new StringableArrayObject([]),
            new StringableArrayObject(['or' => ['term']]),
        ];

        yield [
            '?search=term1+term2',
            new StringableArrayObject([]),
            new StringableArrayObject(['or' => ['term1', 'term2']]),
        ];

        yield [
            '?search={"and": ["term1", "term2"]}',
            new StringableArrayObject([]),
            new StringableArrayObject(['and' => ['term1', 'term2']]),
        ];

        yield [
            '?search={"or": ["term1", "term2"]}',
            new StringableArrayObject([]),
            new StringableArrayObject(['or' => ['term1', 'term2']]),
        ];

        yield [
            '?search={"and": ["term1", "term2"], "or": ["term3", "term4"]}',
            new StringableArrayObject([]),
            new StringableArrayObject(['and' => ['term1', 'term2'], 'or' => ['term3', 'term4']]),
        ];
    }

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $this->responseHandler = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validTestClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
            [$this->resource, $this->responseHandler]
        );

        $this->inValidTestClass = $this->getMockForAbstractClass(IdsMethodInvalidTestClass::class);
    }
}
