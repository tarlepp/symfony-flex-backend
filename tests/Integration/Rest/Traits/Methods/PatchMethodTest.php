<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\PatchMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\PatchMethodTestClass;
use Exception;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class PatchMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class PatchMethodTest extends KernelTestCase
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @codingStandardsIgnoreStart
     *
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' controller class with REST traits if that does not implement 'App\\Rest\\ControllerInterface'/
     *
     * @codingStandardsIgnoreEnd
     *
     * @throws Throwable
     */
    public function testThatTraitThrowsAnException():void
    {
        /**
         * @var MockObject|FormFactoryInterface $formFactoryMock
         * @var MockObject|PatchMethodInvalidTestClass $testClass
         */
        $testClass = $this->getMockForAbstractClass(PatchMethodInvalidTestClass::class);
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid, 'PATCH');

        $testClass->patchMethod($request, $formFactoryMock, 'some-id');
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

        /** @var MockObject|FormFactoryInterface $formFactoryMock */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        /** @var MockObject|PatchMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            PatchMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $uuid = Uuid::uuid4()->toString();

        // Create request and response
        $request = Request::create('/' . $uuid, $httpMethod);

        $testClass->patchMethod($request, $formFactoryMock, 'some-id')->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @param Exception $exception
     * @param integer   $expectedCode
     *
     * @throws Throwable
     */
    public function testThatTraitHandlesException(Exception $exception, int $expectedCode): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|FormFactoryInterface $formFactoryMock */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        /** @var MockObject|PatchMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            PatchMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, 'PATCH');

        $formFactoryMock
            ->expects(static::once())
            ->method('createNamed')
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->patchMethod($request, $formFactoryMock, $uuid);
    }

    /**
     * @throws Throwable
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|PatchMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            PatchMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /**
         * @var MockObject|FormFactoryInterface $formFactoryMock
         * @var MockObject|FormInterface        $formInterfaceMock
         * @var MockObject|FormConfigInterface  $formConfigInterface
         * @var MockObject|RestDtoInterface     $dtoInterface
         * @var MockObject|Request              $request
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
            ->willReturn('PATCH');

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
            ->method('isSubmitted')
            ->willReturn(true);

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

        $testClass->patchMethod($request, $formFactoryMock, $uuid);
    }

    /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @throws Throwable
     */
    public function testThatTraitThrowsAnErrorIfFormIsInvalid(): void
    {
        $resource = $this->createMock(RestResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var MockObject|PatchMethodTestClass $testClass */
        $testClass = $this->getMockForAbstractClass(
            PatchMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /**
         * @var MockObject|FormFactoryInterface $formFactoryMock
         * @var MockObject|FormInterface        $formInterfaceMock
         * @var MockObject|FormConfigInterface  $formConfigInterface
         * @var MockObject|RestDtoInterface     $dtoInterface
         * @var MockObject|Request              $request
         */
        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $formInterfaceMock = $this->getMockBuilder(FormInterface::class)->getMock();
        $formConfigInterface = $this->getMockBuilder(FormConfigInterface::class)->getMock();
        $request = $this->createMock(Request::class);

        $uuid = Uuid::uuid4()->toString();

        $request
            ->expects(static::exactly(2))
            ->method('getMethod')
            ->willReturn('PATCH');

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
            ->method('isSubmitted')
            ->willReturn(true);

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

        $testClass->patchMethod($request, $formFactoryMock, $uuid);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['DELETE'];
        yield ['GET'];
        yield ['POST'];
        yield ['PUT'];
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
        yield [new NotFoundHttpException(), 0];
        yield [new Exception(), 400];
    }
}
