<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/CountMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\CountMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\CountMethodTestClass;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * Class CountMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CountMethodTest extends KernelTestCase
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

        /** @var MockObject|CountMethodInvalidTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(CountMethodInvalidTestClass::class);

        $request = Request::create('/');

        $testClass->countMethod($request);
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\CountMethod` throws an exception with `$httpMethod` HTTP method.
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|CountMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->countMethod($request)->getContent();
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsProcessCriteriaIfItExists(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|CountMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
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

        $testClass->countMethod($request)->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @param Exception $exception
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\CountMethod` uses `$expectedCode` code on HttpException.
     */
    public function testThatTraitHandlesException(\Throwable $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|CountMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/');

        $resource
            ->expects(static::once())
            ->method('count')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->countMethod($request);
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|CountMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            CountMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/count');
        $response = new Response('123');

        $resource
            ->expects(static::once())
            ->method('count')
            ->withAnyParameters()
            ->willReturn(123);

        $responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->withAnyParameters()
            ->willReturn($response);

        $testClass->countMethod($request)->getContent();
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
        yield [new NoResultException(), 404];
        yield [new NonUniqueResultException(), 500];
        yield [new Exception(), 400];
    }
}
