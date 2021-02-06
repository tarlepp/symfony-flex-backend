<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest;

use App\DTO\RestDtoInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\ResponseHandler;
use App\Tests\Integration\Rest\src\AbstractController as Controller;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use UnexpectedValueException;
use function get_class;

/**
 * Class ControllerTest
 *
 * @package App\Tests\Integration\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ControllerTest extends KernelTestCase
{
    /**
     * @var MockObject|RestResourceInterface
     */
    private $resource;

    /**
     * @var MockObject|Controller
     */
    private $controller;

    /**
     * @var MockObject|RestDtoInterface
     */
    private $dtoClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dtoClass = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $this->resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $this->controller = $this->getMockForAbstractClass(Controller::class, [$this->resource])
            ->setResponseHandler(new ResponseHandler(new Serializer()));
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
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(RestResourceInterface::class, $this->controller->getResource());
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
        static::assertInstanceOf(ResponseHandler::class, $this->controller->getResponseHandler());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getDtoClass` method calls expected `Resource` service method
     */
    public function testThatGetDtoClassCallsExpectedServiceMethods(): void
    {
        $this->resource
            ->expects(static::once())
            ->method('getDtoClass')
            ->willReturn(get_class($this->dtoClass));

        $this->controller->getDtoClass();
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

        $this->resource
            ->expects(static::once())
            ->method('getDtoClass')
            ->willReturn(stdClass::class);

        $this->controller->getDtoClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getDtoClass` returns expected when using custom DTO class mapping
     */
    public function testThatGetDtoClassWorksAsExpectedWithGivenDtoClasses(): void
    {
        $dtoClasses = [
            'foo' => get_class($this->dtoClass),
        ];

        $reflection = new ReflectionProperty(get_class($this->controller), 'dtoClasses');
        $reflection->setAccessible(true);
        $reflection->setValue(null, $dtoClasses);

        static::assertSame(get_class($this->dtoClass), $this->controller->getDtoClass('foo'));
    }
}
