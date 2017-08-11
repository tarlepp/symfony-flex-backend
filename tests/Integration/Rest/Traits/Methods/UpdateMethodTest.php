<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/UpdateMethodTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace Integration\Rest\Traits\Methods;

use App\Entity\EntityInterface;
use App\Rest\DTO\RestDto;
use App\Rest\DTO\RestDtoInterface;
use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\Methods\UpdateMethod;
use App\Tests\Integration\Rest\Traits\Methods\src\UpdateMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\UpdateMethodTestClass;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class UpdateMethodTest
 *
 * @package Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UpdateMethodTest extends KernelTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' controller class with REST traits if that does not implement 'App\\Rest\\ControllerInterface'/
     */
    public function testThatTraitThrowsAnException():void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock
         * @var \PHPUnit_Framework_MockObject_MockObject|UpdateMethodInvalidTestClass $testClass
         */
        $testClass = $this->getMockForAbstractClass(UpdateMethodInvalidTestClass::class);
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid, 'PUT');

        $testClass->updateMethod($request, $formFactoryMock, 'some-id');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     *
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @param string $httpMethod
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        /** @var UpdateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $uuid = Uuid::uuid4()->toString();

        // Create request and response
        $request = Request::create('/' . $uuid, $httpMethod);

        $testClass->updateMethod($request, $formFactoryMock, 'some-id')->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @param \Exception $exception
     * @param integer    $expectedCode
     */
    public function testThatTraitHandlesException(\Exception $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|UpdateMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, 'PUT');

        $formFactoryMock
            ->expects(static::once())
            ->method('createNamed')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->updateMethod($request, $formFactoryMock, $uuid);
    }

    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|UpdateMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock
         * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface        $formInterfaceMock
         * @var \PHPUnit_Framework_MockObject_MockObject|FormConfigInterface  $formConfigInterface
         * @var \PHPUnit_Framework_MockObject_MockObject|RestDtoInterface     $dtoInterface
         * @var \PHPUnit_Framework_MockObject_MockObject|Request              $request
         */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $formInterfaceMock = $this->getMockBuilder(FormInterface::class)->getMock();
        $formConfigInterface = $this->getMockBuilder(FormConfigInterface::class)->getMock();
        $dtoInterface = $this->createMock(RestDtoInterface::class);
        $request = $this->createMock(Request::class);

        $uuid = Uuid::uuid4()->toString();

        $request
            ->expects(static::exactly(2))
            ->method('getMethod')
            ->willReturn('PUT');

        $formConfigInterface
            ->expects(static::once())
            ->method('getDataClass')
            ->willReturn('foobar');

        $formInterfaceMock
            ->expects(static::once())
            ->method('setData')
            ->withAnyParameters();

        $formInterfaceMock
            ->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $formInterfaceMock
            ->expects(static::once())
            ->method('getConfig')
            ->willReturn($formConfigInterface);

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

        $testClass->updateMethod($request, $formFactoryMock, $uuid);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testThatTraitThrowsAnErrorIfFormIsInvalid(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|UpdateMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactoryMock
         * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface        $formInterfaceMock
         * @var \PHPUnit_Framework_MockObject_MockObject|FormConfigInterface  $formConfigInterface
         * @var \PHPUnit_Framework_MockObject_MockObject|RestDtoInterface     $dtoInterface
         * @var \PHPUnit_Framework_MockObject_MockObject|Request              $request
         */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $formInterfaceMock = $this->getMockBuilder(FormInterface::class)->getMock();
        $formConfigInterface = $this->getMockBuilder(FormConfigInterface::class)->getMock();
        $request = $this->createMock(Request::class);

        $uuid = Uuid::uuid4()->toString();

        $request
            ->expects(static::exactly(2))
            ->method('getMethod')
            ->willReturn('PUT');

        $formConfigInterface
            ->expects(static::once())
            ->method('getDataClass')
            ->willReturn('foobar');

        $formInterfaceMock
            ->expects(static::once())
            ->method('setData')
            ->withAnyParameters();

        $formInterfaceMock
            ->expects(static::once())
            ->method('handleRequest')
            ->with($request);

        $formInterfaceMock
            ->expects(static::once())
            ->method('getConfig')
            ->willReturn($formConfigInterface);

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

        $testClass->updateMethod($request, $formFactoryMock, $uuid);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): array
    {
        return [
            ['HEAD'],
            ['DELETE'],
            ['GET'],
            ['PATCH'],
            ['POST'],
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
            [new NotFoundHttpException(), 0],
            [new \Exception(), 400],
        ];
    }
}
