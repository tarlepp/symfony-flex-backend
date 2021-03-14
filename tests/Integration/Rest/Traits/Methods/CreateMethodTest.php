<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/CreateMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodTestClass;
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
 * Class CreateMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CreateMethodTest extends KernelTestCase
{
    private MockObject | RestDtoInterface | null $restDto = null;
    private MockObject | EntityInterface | null $entity = null;
    private MockObject | RestResourceInterface | null $resource = null;
    private MockObject | ResponseHandlerInterface | null $responseHandler = null;
    private MockObject | CreateMethodTestClass | null $validTestClass = null;
    private MockObject | CreateMethodInvalidTestClass | null $inValidTestClass = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restDto = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $this->entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $this->resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $this->responseHandler = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validTestClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$this->resource, $this->responseHandler]
        );

        $this->inValidTestClass = $this->getMockForAbstractClass(CreateMethodInvalidTestClass::class);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `createMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /* @codingStandardsIgnoreEnd */

        $this->getInValidTestClass()->createMethod(Request::create('/', 'POST'), $this->getRestDto());
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `createMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->getValidTestClass()
            ->createMethod(Request::create('/', $httpMethod), $this->getRestDto())
            ->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `createMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatHandleRestMethodExceptionIsCalled(Throwable $exception, int $expectedCode): void
    {
        $this->getResourceMock()
            ->expects(static::once())
            ->method('create')
            ->with($this->restDto, true)
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $this->getValidTestClass()
            ->createMethod(Request::create('/', 'POST'), $this->getRestDto())
            ->getContent();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `createMethod` method calls expected service methods
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $request = Request::create('/', 'POST');

        $this->getResourceMock()
            ->expects(static::once())
            ->method('create')
            ->with($this->restDto, true)
            ->willReturn($this->entity);

        $this->getResponseHandlerMock()
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $this->entity, $this->resource, 201);

        $this->getValidTestClass()->createMethod($request, $this->getRestDto());
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['GET'];
        yield ['PATCH'];
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

    private function getValidTestClass(): CreateMethodTestClass
    {
        assert($this->validTestClass instanceof CreateMethodTestClass);

        return $this->validTestClass;
    }

    private function getInValidTestClass(): CreateMethodInvalidTestClass
    {
        assert($this->inValidTestClass instanceof CreateMethodInvalidTestClass);

        return $this->inValidTestClass;
    }

    private function getRestDto(): RestDtoInterface
    {
        assert($this->restDto instanceof RestDtoInterface);

        return $this->restDto;
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
