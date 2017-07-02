<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindMethodTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\Methods\FindMethod;
use App\Tests\Integration\Rest\Traits\Methods\src\FindMethodTestClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class FindMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class FindMethodTest extends KernelTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' within controller class that does not implement 'App\\Rest\\ControllerInterface'/
     */
    public function testThatTraitThrowsAnException():void
    {
        /** @var FindMethod $mock */
        $mock = $this->getMockForTrait(FindMethod::class);
        $request = Request::create('/');

        $mock->findMethod($request);
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
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->findMethod($request)->getContent();
    }

    public function testThatTraitCallsProcessCriteriaIfItExists(): void
    {
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
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
     * @param   \Exception  $exception
     * @param   int         $expectedCode
     */
    public function testThatTraitHandlesException(\Exception $exception, int $expectedCode): void
    {
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/');

        $resource
            ->expects(static::once())
            ->method('find')
            ->willThrowException($exception);

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->findMethod($request);
    }

    public function testThatTraitCallsServiceMethods()
    {
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', 'GET');
        $response = Response::create('[]');

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

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $testClass
            ->expects(static::once())
            ->method('getResponseHandler')
            ->willReturn($responseHandler);

        $testClass->findMethod($request);
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
