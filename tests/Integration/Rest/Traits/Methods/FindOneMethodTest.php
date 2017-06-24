<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindOneMethodTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace Integration\Rest\Traits\Methods;

use App\Entity\EntityInterface;
use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\Methods\FindOneMethod;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodTestClass;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class FindOneMethodTest
 *
 * @package Integration\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class FindOneMethodTest extends KernelTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /You cannot use '.*' within controller class that does not implement 'App\\Rest\\ControllerInterface'/
     */
    public function testThatTraitThrowsAnException():void
    {
        /** @var FindOneMethod $mock */
        $mock = $this->getMockForTrait(FindOneMethod::class);

        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid);

        $mock->findOneMethod($request, 'some-id');
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
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindOneMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
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
     * @param \Exception $exception
     * @param integer    $expectedCode
     */
    public function testThatTraitHandlesException(\Exception $exception, int $expectedCode): void
    {
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindOneMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
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

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->findOneMethod($request, $uuid);
    }

    public function testThatTraitCallsServiceMethods()
    {
        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var FindOneMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            FindOneMethodTestClass::class,
            [$resource, $responseHandler]
        );

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
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

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $testClass
            ->expects(static::once())
            ->method('getResponseHandler')
            ->willReturn($responseHandler);

        $testClass->findOneMethod($request, $uuid);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): array
    {
        return [
            ['HEAD'],
            ['DELETE'],
            ['PUT'],
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
