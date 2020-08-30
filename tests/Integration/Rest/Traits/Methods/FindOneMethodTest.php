<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindOneMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodTestClass;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class FindOneMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class FindOneMethodTest extends KernelTestCase
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

        /** @var MockObject|FindOneMethodInvalidTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(FindOneMethodInvalidTestClass::class);

        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid);

        $testClass->findOneMethod($request, 'some-id');
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\FindOneMethod` throws an exception with `$httpMethod` HTTP method.
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindOneMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindOneMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $uuid = Uuid::uuid4()->toString();

        // Create request and response
        $request = Request::create('/' . $uuid, $httpMethod);

        $testClass->findOneMethod($request, 'some-id')->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @param Exception $exception
     *
     * @throws Throwable
     *
     * @testdox Test that `App\Rest\Traits\Methods\FindOneMethod` uses `$expectedCode` code on HttpException.
     */
    public function testThatTraitHandlesException(\Throwable $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindOneMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindOneMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid);

        $resource
            ->expects(static::once())
            ->method('findOne')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->findOneMethod($request, $uuid);
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FindOneMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindOneMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /** @var MockObject|Request $request */
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $entityInterface = $this->createMock(EntityInterface::class);

        $uuid = Uuid::uuid4()->toString();

        $request
            ->expects(static::once())
            ->method('getMethod')
            ->willReturn('GET');

        $resource
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid, true)
            ->willReturn($entityInterface);

        $responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->withAnyParameters()
            ->willReturn($response);

        $testClass->findOneMethod($request, $uuid);
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
        yield [new HttpException(400), 0];
        yield [new NotFoundHttpException(), 0];
        yield [new LogicException(), 400];
        yield [new InvalidArgumentException(), 400];
        yield [new Exception(), 400];
    }
}
