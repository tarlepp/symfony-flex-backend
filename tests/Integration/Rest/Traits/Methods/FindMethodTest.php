<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\FindMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\FindMethodTestClass;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * Class FindMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class FindMethodTest extends KernelTestCase
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

        /** @var MockObject|FindMethodInvalidTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(FindMethodInvalidTestClass::class);

        $request = Request::create('/');

        $testClass->findMethod($request);
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\FindMethod` throws an exception with `$httpMethod` HTTP method.
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->findMethod($request)->getContent();
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsProcessCriteriaIfItExists(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler],
            '',
            true,
            true,
            true,
            ['processCriteria']
        );

        // Create request
        $request = Request::create('/');

        $testClass
            ->expects(static::once())
            ->method('processCriteria')
            ->withAnyParameters();

        $testClass->findMethod($request)->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @param Exception $exception
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\FindMethod` uses `$expectedCode` code on HttpException.
     */
    public function testThatTraitHandlesException(\Throwable $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/');

        $resource
            ->expects(static::once())
            ->method('find')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->findMethod($request);
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/');
        $response = new Response('[]');

        $resource
            ->expects(static::once())
            ->method('find')
            ->withAnyParameters()
            ->willReturn([]);

        $responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->withAnyParameters()
            ->willReturn($response);

        $testClass->findMethod($request);
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
        yield [new HttpException(400), 0];
        yield [new LogicException(), 400];
        yield [new InvalidArgumentException(), 400];
        yield [new Exception(), 400];
    }
}
