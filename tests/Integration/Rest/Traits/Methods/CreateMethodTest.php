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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class CreateMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CreateMethodTest extends KernelTestCase
{
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

        $this
            ->getMockForAbstractClass(CreateMethodInvalidTestClass::class)
            ->createMethod(Request::create('/', 'POST'), $this->getMockBuilder(RestDtoInterface::class)->getMock());
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

        $restResourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validTestClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$restResourceMock, $responseHandlerMock],
        );

        $validTestClass
            ->createMethod(Request::create('/', $httpMethod), $this->getMockBuilder(RestDtoInterface::class)->getMock())
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
        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $restResourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restResourceMock
            ->expects(static::once())
            ->method('create')
            ->with($restDtoMock, true)
            ->willThrowException($exception);

        $validTestClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$restResourceMock, $responseHandlerMock],
        );

        $validTestClass->createMethod(Request::create('/', 'POST'), $restDtoMock)->getContent();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `createMethod` method calls expected service methods
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $request = Request::create('/', 'POST');

        $entityMock = $this->getMockBuilder(EntityInterface::class)->getMock();
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $restResourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restResourceMock
            ->expects(static::once())
            ->method('create')
            ->with($restDtoMock, true)
            ->willReturn($entityMock);

        $responseHandlerMock
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $entityMock, $restResourceMock, 201);

        $validTestClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$restResourceMock, $responseHandlerMock],
        );

        $validTestClass->createMethod($request, $restDtoMock);
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
}
