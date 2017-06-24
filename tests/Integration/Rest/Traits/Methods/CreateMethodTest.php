<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/CreateMethodTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Entity\EntityInterface;
use App\Rest\DTO\RestDto;
use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\Methods\CreateMethod;
use App\Tests\Integration\Rest\Traits\Methods\src\CreateMethodTestClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;

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
     * @expectedExceptionMessageRegExp /You cannot use '.*' within controller class that does not implement 'App\\Rest\\ControllerInterface'/
     */
    public function testThatTraitThrowsAnException():void
    {
        /** @var CreateMethod $mock */
        $mock = $this->getMockForTrait(CreateMethod::class);
        $request = Request::create('/');

        $mock->createMethod($request);
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

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        // Create request and response
        $request = Request::create('/', $httpMethod);

        $testClass->createMethod($request)->getContent();
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

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request = Request::create('/', 'POST');

        $resource
            ->expects(static::once())
            ->method('getDtoClass')
            ->willThrowException($exception);

        $testClass
            ->expects(static::once())
            ->method('getResource')
            ->willReturn($resource);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClass->createMethod($request);
    }

    public function testThatTraitCallsServiceMethods(): void
    {
        self::bootKernel();

        $resource = $this->createMock(ResourceInterface::class);
        $responseHandler = $this->createMock(ResponseHandlerInterface::class);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $entityInterface = $this->createMock(EntityInterface::class);
        $dtoInterface = $this->createMock(RestDto::class);
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var CreateMethodTestClass|\PHPUnit_Framework_MockObject_MockObject $testClass */
        $testClass = $this->getMockForAbstractClass(
            CreateMethodTestClass::class,
            [$resource, $responseHandler]
        );

        $request
            ->expects(static::once())
            ->method('getContent')
            ->willReturn('{"foo":"bar"}');

        $request
            ->expects(static::once())
            ->method('getMethod')
            ->willReturn('POST');

        $resource
            ->expects(static::once())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($entityInterface);

        $serializer
            ->expects(static::once())
            ->method('deserialize')
            ->withAnyParameters()
            ->willReturn($dtoInterface);

        $responseHandler
            ->expects(static::once())
            ->method('getSerializer')
            ->willReturn($serializer);

        $responseHandler
            ->expects(static::once())
            ->method('createResponse')
            ->withAnyParameters()
            ->willReturn($response);

        $testClass
            ->expects(static::exactly(2))
            ->method('getResource')
            ->willReturn($resource);

        $testClass
            ->expects(static::exactly(2))
            ->method('getResponseHandler')
            ->willReturn($responseHandler);

        $testClass->createMethod($request);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): array
    {
        return [
            ['HEAD'],
            ['GET'],
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
