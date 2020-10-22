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
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class UpdateMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UpdateMethodTest extends KernelTestCase
{
    /**
     * @var MockObject|RestDtoInterface
     */
    private $restDto;

    /**
     * @var MockObject|EntityInterface
     */
    private $entity;

    /**
     * @var MockObject|RestResourceInterface
     */
    private $resource;

    /**
     * @var MockObject|ResponseHandlerInterface
     */
    private $responseHandler;

    /**
     * @var MockObject|UpdateMethodTestClass
     */
    private $validTestClass;

    /**
     * @var MockObject|UpdateMethodInvalidTestClass
     */
    private $inValidTestClass;

    /**
     * @throws Throwable
     *
     * @testdox Test that `updateMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /** @codingStandardsIgnoreEnd */

        $request = Request::create('/' . Uuid::uuid4()->toString(), 'PUT');

        $this->inValidTestClass->updateMethod($request, $this->restDto, 'some-id');
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `updateMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $request = Request::create('/' . Uuid::uuid4()->toString(), $httpMethod);

        $this->validTestClass->updateMethod($request, $this->restDto, 'some-id')->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `updateMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, 'PUT');

        $this->resource
            ->expects(static::once())
            ->method('update')
            ->with($uuid, $this->restDto, true)
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $this->validTestClass->updateMethod($request, $this->restDto, $uuid);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `updateMethod` method calls expected service methods
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid, 'PUT');

        $this->resource
            ->expects(static::once())
            ->method('update')
            ->with($uuid, $this->restDto, true)
            ->willReturn($this->entity);

        $this->responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $this->entity, $this->resource);

        $this->validTestClass->updateMethod($request, $this->restDto, $uuid);
    }

    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['DELETE'];
        yield ['GET'];
        yield ['PATCH'];
        yield ['POST'];
        yield ['OPTIONS'];
        yield ['CONNECT'];
        yield ['foobar'];
    }

    public function dataProviderTestThatTraitHandlesException(): Generator
    {
        yield [new HttpException(400), 400];
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

        $this->restDto = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $this->entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $this->resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $this->responseHandler = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validTestClass = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$this->resource, $this->responseHandler]
        );

        $this->inValidTestClass = $this->getMockForAbstractClass(UpdateMethodInvalidTestClass::class);
    }
}
