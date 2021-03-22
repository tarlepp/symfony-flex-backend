<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindOneMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodTestClass;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use function assert;

/**
 * Class FindOneMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneMethodTest extends KernelTestCase
{
    private MockObject | RestResourceInterface | null $resource = null;
    private MockObject | EntityInterface | null $entity = null;
    private MockObject | ResponseHandlerInterface | null $responseHandler = null;
    private MockObject | FindOneMethodTestClass | null $validTestClass = null;
    private MockObject | FindOneMethodInvalidTestClass | null $inValidTestClass = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $this->entity = $this->getMockBuilder(EntityInterface::class)->getMock();

        $this->responseHandler = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validTestClass = $this->getMockForAbstractClass(
            FindOneMethodTestClass::class,
            [$this->resource, $this->responseHandler]
        );

        $this->inValidTestClass = $this->getMockForAbstractClass(FindOneMethodInvalidTestClass::class);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /* @codingStandardsIgnoreEnd */

        $this->getInValidTestClass()->findOneMethod(Request::create('/' . Uuid::uuid4()->toString()), 'some-id');
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->getValidTestClass()
            ->findOneMethod(Request::create('/' . Uuid::uuid4()->toString(), $httpMethod), 'some-id')
            ->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $uuid = Uuid::uuid4()->toString();

        $this->getResourceMock()
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid)
            ->willThrowException($exception);

        $this->getValidTestClass()->findOneMethod(Request::create('/' . $uuid), $uuid);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` method calls expected service methods
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid);

        $this->getResourceMock()
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid, true)
            ->willReturn($this->entity);

        $this->getResponseHandlerMock()
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $this->entity, $this->resource);

        $this->getValidTestClass()->findOneMethod($request, $uuid);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['DELETE'];
        yield ['PATCH'];
        yield ['PUT'];
        yield ['POST'];
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

    private function getValidTestClass(): FindOneMethodTestClass
    {
        assert($this->validTestClass instanceof FindOneMethodTestClass);

        return $this->validTestClass;
    }

    private function getInValidTestClass(): FindOneMethodInvalidTestClass
    {
        assert($this->inValidTestClass instanceof FindOneMethodInvalidTestClass);

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
