<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\Methods\IdsMethod;
use App\Tests\Integration\Rest\Traits\Methods\src\IdsMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\IdsMethodTestClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class IdsMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IdsMethodTest extends KernelTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' controller class with REST traits if that does not implement 'App\\Rest\\ControllerInterface'/
     */
    public function testThatTraitThrowsAnException():void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|IdsMethodInvalidTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(IdsMethodInvalidTestClass::class);

        $request = Request::create('/');

        $testClass->idsMethod($request);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     *
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @param   string  $httpMethod
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var IdsMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->idsMethod($request)->getContent();
    }

    public function testThatTraitCallsProcessCriteriaIfItExists(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var IdsMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
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
     * @param   \Exception  $exception
     * @param   int         $expectedCode
     */
    public function testThatTraitHandlesException(\Exception $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var IdsMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            IdsMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/');

        $resource
            ->expects(static::once())
            ->method('getIds')
            ->willThrowException($exception);

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->idsMethod($request);
    }

    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var IdsMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
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

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $testClass
            ->expects(static::once())
            ->method('getResponseHandler')
            ->willReturn($responseHandler);

        $testClass->idsMethod($request);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): array
    {
        return [
            ['HEAD'],
            ['PATCH'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTraitHandlesException(): array
    {
        return [
            [new HttpException(400), 0],
            [new \LogicException(), 400],
            [new \InvalidArgumentException(), 400],
            [new \Exception(), 400],
        ];
    }
}
