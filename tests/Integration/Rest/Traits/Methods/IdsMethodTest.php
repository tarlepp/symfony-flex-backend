<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\ResponseHandlerInterface;
use App\Rest\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\IdsMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\IdsMethodTestClass;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Class IdsMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IdsMethodTest extends KernelTestCase
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @codingStandardsIgnoreStart
     *
     * @expectedException LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' controller class with REST traits if that does not implement 'App\\Rest\\ControllerInterface'/
     *
     * @codingStandardsIgnoreEnd
     *
     * @throws Throwable
     */
    public function testThatTraitThrowsAnException():void
    {
        /** @var MockObject|IdsMethodInvalidTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(IdsMethodInvalidTestClass::class);

        $request = Request::create('/');

        $testClass->idsMethod($request);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     *
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @param string $httpMethod
     *
     * @throws Throwable
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|IdsMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->idsMethod($request)->getContent();
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsProcessCriteriaIfItExists(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|IdsMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
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

        $testClass->idsMethod($request)->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @param Exception $exception
     * @param int       $expectedCode
     *
     * @throws Throwable
     */
    public function testThatTraitHandlesException(Exception $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|IdsMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/');

        $resource
            ->expects(static::once())
            ->method('getIds')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->idsMethod($request);
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|IdsMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/');
        $response = Response::create('[]');

        $resource
            ->expects(static::once())
            ->method('getIds')
            ->withAnyParameters()
            ->willReturn([]);

        $responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->withAnyParameters()
            ->willReturn($response);

        $testClass->idsMethod($request);
    }

    /**
     * @return Generator
     */
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

    /**
     * @return Generator
     */
    public function dataProviderTestThatTraitHandlesException(): Generator
    {
        yield [new HttpException(400), 0];
        yield [new LogicException(), 400];
        yield [new InvalidArgumentException(), 400];
        yield [new Exception(), 400];
    }
}
