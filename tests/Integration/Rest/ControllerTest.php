<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest;

use App\Controller\v1\ApiKey\ApiKeyController;
use App\DTO\ApiKey\ApiKey;
use App\DTO\RestDtoInterface;
use App\Resource\ApiKeyResource;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Tests\Utils\PhpUnitUtil;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use UnexpectedValueException;

/**
 * @package App\Tests\Integration\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class ControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that `getResource` method doesn't throw an exception if `Resource` service is set")]
    public function testThatGetResourceDoesNotThrowsAnExceptionIfSet(): void
    {
        $this->expectNotToPerformAssertions();

        $resourceMock = $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();

        $controller = new ApiKeyController($resourceMock);
        $controller->setResponseHandler(new ResponseHandler(new Serializer()));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getResponseHandler` method throws an exception if `ResponseHandler` service is not set')]
    public function testThatGetResponseHandlerThrowsAnExceptionIfNotSet(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('ResponseHandler service not set');

        $class = new class() extends Controller {
            public function __construct()
            {
            }
        };

        $class->getResponseHandler();
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that `getResponseHandler` method doesn't throw an exception if `ResponseHandler` service is set")]
    public function testThatGetResponseHandlerDoesNotThrowsAnExceptionIfSet(): void
    {
        $resourceMock = $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();

        $controller = new ApiKeyController($resourceMock);
        $controller->setResponseHandler(new ResponseHandler(new Serializer()));

        self::assertInstanceOf(ResponseHandler::class, $controller->getResponseHandler());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getDtoClass` method calls expected `Resource` service method')]
    public function testThatGetDtoClassCallsExpectedServiceMethods(): void
    {
        $dtoClassMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $resourceMock = $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();

        $controller = new ApiKeyController($resourceMock);
        $controller->setResponseHandler(new ResponseHandler(new Serializer()));

        $resourceMock
            ->expects($this->once())
            ->method('getDtoClass')
            ->willReturn($dtoClassMock::class);

        $controller->getDtoClass();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getDtoClass` method throws an exception when `Resource` class not returning expected')]
    public function testThatGetDtoClassThrowsAnExceptionIfResourceDoesNotReturnExpectedClass(): void
    {
        $resourceMock = $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();

        $controller = new ApiKeyController($resourceMock);
        $controller->setResponseHandler(new ResponseHandler(new Serializer()));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Given DTO class \'stdClass\' is not implementing \'App\DTO\RestDtoInterface\' interface.'
        );

        $resourceMock
            ->expects($this->once())
            ->method('getDtoClass')
            ->willReturn(stdClass::class);

        $controller->getDtoClass();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getDtoClass` returns expected when using custom DTO class mapping')]
    public function testThatGetDtoClassWorksAsExpectedWithGivenDtoClasses(): void
    {
        $resourceMock = $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();

        $controller = new ApiKeyController($resourceMock);
        $controller->setResponseHandler(new ResponseHandler(new Serializer()));

        $dtoClasses = [
            'foo' => ApiKey::class,
        ];

        PhpUnitUtil::setProperty('dtoClasses', $dtoClasses, $controller);

        self::assertSame(ApiKey::class, $controller->getDtoClass('foo'));
    }
}
