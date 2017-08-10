<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/CreateMethodTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Rest\DTO\RestDtoInterface;
use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodTestClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class CreateMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateMethodTest extends KernelTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' controller class with REST traits if that does not implement 'App\\Rest\\ControllerInterface'/
     */
    public function testThatTraitThrowsAnException():void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock
         * @var \PHPUnit_Framework_MockObject_MockObject|CreateMethodInvalidTestClass $testClass
         */
        $testClass = $this->getMockForAbstractClass(CreateMethodInvalidTestClass::class);
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        $request = Request::create('/');

        $testClass->createMethod($request, $formFactoryMock);
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->createMethod($request, $formFactoryMock)->getContent();
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/', 'POST');

        $formFactoryMock
            ->expects(static::once())
            ->method('createNamed')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->createMethod($request, $formFactoryMock);
    }

    public function testThatTraitCallsServiceMethods(): void
    {
        self::bootKernel();

        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock
         * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface        $formInterfaceMock
         * @var \PHPUnit_Framework_MockObject_MockObject|RestDtoInterface     $dtoInterface
         * @var \PHPUnit_Framework_MockObject_MockObject|Request              $request
         */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $formInterfaceMock = $this->getMockBuilder(FormInterface::class)->getMock();
        $dtoInterface = $this->createMock(RestDtoInterface::class);
        $request = $this->createMock(Request::class);

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request
            ->expects(static::exactly(2))
            ->method('getMethod')
            ->willReturn('POST');

        $formInterfaceMock
            ->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $formInterfaceMock
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $formInterfaceMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn($dtoInterface);

        $formFactoryMock
            ->expects(static::once())
            ->method('createNamed')
            ->withAnyParameters()
            ->willReturn($formInterfaceMock);

        $testClass->createMethod($request, $formFactoryMock);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testThatTraitThrowsAnErrorIfFormIsInvalid(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock
         * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface        $formInterfaceMock
         * @var \PHPUnit_Framework_MockObject_MockObject|Request              $request
         */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $formInterfaceMock = $this->getMockBuilder(FormInterface::class)->getMock();
        $request = $this->createMock(Request::class);

        $request
            ->expects(static::exactly(2))
            ->method('getMethod')
            ->willReturn('POST');

        $formInterfaceMock
            ->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $formInterfaceMock
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(false);

        $formFactoryMock
            ->expects(static::once())
            ->method('createNamed')
            ->withAnyParameters()
            ->willReturn($formInterfaceMock);

        $responseHandler
            ->expects(static::once())
            ->method('handleFormError')
            ->willThrowException(new HttpException(400));

        $testClass
            ->expects(static::once())
            ->method('getResponseHandler')
            ->willReturn($responseHandler);

        $testClass->createMethod($request, $formFactoryMock);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): array
    {
        return [
            ['HEAD'],
            ['GET'],
            ['PATCH'],
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
