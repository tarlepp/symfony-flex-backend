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

/**
 * Class FindOneMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneMethodTest extends KernelTestCase
{
    /**
     * @var MockObject|RestResourceInterface
     */
    private $resource;

    /**
     * @var MockObject|EntityInterface
     */
    private $entity;

    /**
     * @var MockObject|ResponseHandlerInterface
     */
    private $responseHandler;

    /**
     * @var MockObject|FindOneMethodTestClass
     */
    private $validTestClass;

    /**
     * @var MockObject|FindOneMethodInvalidTestClass
     */
    private $inValidTestClass;

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
        /** @codingStandardsIgnoreEnd */

        $this->inValidTestClass->findOneMethod(Request::create('/' . Uuid::uuid4()->toString()), 'some-id');
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

        $this->validTestClass
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

        $this->resource
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid)
            ->willThrowException($exception);

        $this->validTestClass->findOneMethod(Request::create('/' . $uuid), $uuid);
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

        $this->resource
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid, true)
            ->willReturn($this->entity);

        $this->responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $this->entity, $this->resource);

        $this->validTestClass->findOneMethod($request, $uuid);
    }

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
}
