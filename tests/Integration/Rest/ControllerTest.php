<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest;

use App\Controller\ApiKeyController;
use App\DTO\ApiKey\ApiKey;
use App\DTO\RestDtoInterface;
use App\Resource\ApiKeyResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\ResponseHandler;
use App\Tests\Integration\Rest\src\AbstractController as Controller;
use App\Utils\Tests\PhpUnitUtil;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use UnexpectedValueException;
use function assert;
use function get_class;

/**
 * Class ControllerTest
 *
 * @package App\Tests\Integration\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ControllerTest extends KernelTestCase
{
    private MockObject | RestDtoInterface | null $dtoClass = null;
    private MockObject | ApiKeyResource | null $resource = null;
    private ApiKeyController | null $controller = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dtoClass = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $this->resource = $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();
        $this->controller = new ApiKeyController($this->getResource());
        $this->controller->setResponseHandler(new ResponseHandler(new Serializer()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getResource` method throws an exception if `Resource` service is not set
     */
    public function testThatGetResourceThrowsAnExceptionIfNotSet(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Resource service not set');

        $this->getMockForAbstractClass(Controller::class, [], '', false)
            ->getResource();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getResource` method doesn't throw an exception if `Resource` service is set
     */
    public function testThatGetResourceDoesNotThrowsAnExceptionIfSet(): void
    {
        static::assertInstanceOf(RestResourceInterface::class, $this->getController()->getResource());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getResponseHandler` method throws an exception if `ResponseHandler` service is not set
     */
    public function testThatGetResponseHandlerThrowsAnExceptionIfNotSet(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('ResponseHandler service not set');

        $this->getMockForAbstractClass(Controller::class, [], '', false)
            ->getResponseHandler();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getResponseHandler` method doesn't throw an exception if `ResponseHandler` service is set
     */
    public function testThatGetResponseHandlerDoesNotThrowsAnExceptionIfSet(): void
    {
        static::assertInstanceOf(ResponseHandler::class, $this->getController()->getResponseHandler());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getDtoClass` method calls expected `Resource` service method
     */
    public function testThatGetDtoClassCallsExpectedServiceMethods(): void
    {
        $this->getResourceMock()
            ->expects(static::once())
            ->method('getDtoClass')
            ->willReturn(get_class($this->getDtoClass()));

        $this->getController()->getDtoClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getDtoClass` method throws an exception when `Resource` class not returning expected
     */
    public function testThatGetDtoClassThrowsAnExceptionIfResourceDoesNotReturnExpectedClass(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Given DTO class \'stdClass\' is not implementing \'App\DTO\RestDtoInterface\' interface.'
        );

        $this->getResourceMock()
            ->expects(static::once())
            ->method('getDtoClass')
            ->willReturn(stdClass::class);

        $this->getController()->getDtoClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getDtoClass` returns expected when using custom DTO class mapping
     */
    public function testThatGetDtoClassWorksAsExpectedWithGivenDtoClasses(): void
    {
        $dtoClasses = [
            'foo' => ApiKey::class,
        ];

        $controller = $this->getController();

        PhpUnitUtil::setProperty('dtoClasses', $dtoClasses, $controller);

        static::assertSame(ApiKey::class, $controller->getDtoClass('foo'));
    }

    private function getDtoClass(): RestDtoInterface
    {
        assert($this->dtoClass instanceof RestDtoInterface);

        return $this->dtoClass;
    }

    private function getController(): ApiKeyController
    {
        assert($this->controller instanceof ApiKeyController);

        return $this->controller;
    }

    private function getResource(): ApiKeyResource
    {
        assert($this->resource instanceof ApiKeyResource);

        return $this->resource;
    }

    private function getResourceMock(): MockObject
    {
        assert($this->resource instanceof MockObject);

        return $this->resource;
    }
}
