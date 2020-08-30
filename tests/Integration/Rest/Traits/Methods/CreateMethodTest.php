<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/CreateMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodTestClass;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * Class CreateMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /** @codingStandardsIgnoreEnd */

        /**
         * @var MockObject|CreateMethodInvalidTestClass $testClass
         * @var MockObject|RestDtoInterface $restDtoInterface
         */
        $testClass = $this->getMockForAbstractClass(CreateMethodInvalidTestClass::class);
        $restDtoInterface = $this->getMockBuilder(RestDtoInterface::class)->getMock();

        $request = Request::create('/');

        $testClass->createMethod($request, $restDtoInterface);
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\CreateMethod` throws an exception with `$httpMethod` HTTP method.
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|RestDtoInterface $restDtoInterface */
        $restDtoInterface = $this->getMockBuilder(RestDtoInterface::class)->getMock();

        /** @var MockObject|CreateMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->createMethod($request, $restDtoInterface)->getContent();
    }

    /**
     * @throws Throwable
     */
    public function testThatHandleRestMethodExceptionIsCalled(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('some message');

        /**
         * @var MockObject|RestResourceInterface $resource
         * @var MockObject|ResponseHandlerInterface $responseHandler
         * @var MockObject|RestDtoInterface $restDtoInterface
         */
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);
        $restDtoInterface = $this->getMockBuilder(RestDtoInterface::class)->getMock();

        $exception = new Exception('some message');

        $resource
            ->expects(static::once())
            ->method('create')
            ->with($restDtoInterface, true)
            ->willThrowException($exception);

        /** @var MockObject|CreateMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', 'POST');

        $testClass->createMethod($request, $restDtoInterface)->getContent();
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        static::bootKernel();

        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /**
         * @var MockObject|RestDtoInterface $restDtoInterface
         * @var MockObject|Request $request
         */
        $restDtoInterface = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $request = $this->createMock(Request::class);

        /** @var MockObject|CreateMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request
            ->expects(static::once())
            ->method('getMethod')
            ->willReturn('POST');

        $testClass->createMethod($request, $restDtoInterface);
    }

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

    public function dataProviderTestThatTraitHandlesException(): Generator
    {
        yield [new HttpException(400), 0];
        yield [new LogicException(), 400];
        yield [new InvalidArgumentException(), 400];
        yield [new Exception(), 400];
    }
}
