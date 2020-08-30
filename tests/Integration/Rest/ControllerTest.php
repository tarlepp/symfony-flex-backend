<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
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
     */
    public function testThatGetResourceDoesNotThrowsAnExceptionIfSet(): void
    {
        /**
         * @var RestResourceInterface $resource
         * @var Controller $controller
         */
        $resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $controller = $this->getMockForAbstractClass(Controller::class, [$resource]);

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(RestResourceInterface::class, $controller->getResource());
    }

    /**
     * @throws Throwable
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
     */
    public function testThatGetResponseHandlerDoesNotThrowsAnExceptionIfSet(): void
    {
        /** @var RestResourceInterface $resource */
        $resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $controller = $this->getMockForAbstractClass(Controller::class, [$resource])
            ->setResponseHandler(new ResponseHandler(new Serializer()));

        static::assertInstanceOf(ResponseHandler::class, $controller->getResponseHandler());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetDtoClassCallsExpectedServiceMethods(): void
    {
        /**
         * @var MockObject|RestDtoInterface $dtoClass
         * @var MockObject|RestResourceInterface $resource
         */
        $dtoClass = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $resource
            ->expects(static::once())
            ->method('getDtoClass')
            ->willReturn(get_class($dtoClass));

        $this->getMockForAbstractClass(Controller::class, [$resource])
            ->getDtoClass();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetDtoClassThrowsAnExceptionIfResourceDoesNotReturnExpectedClass(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Given DTO class \'stdClass\' is not implementing \'App\DTO\RestDtoInterface\' interface.'
        );

        /** @var MockObject|RestResourceInterface $resource */
        $resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();

        $resource
            ->expects(static::once())
            ->method('getDtoClass')
            ->willReturn(stdClass::class);

        $this->getMockForAbstractClass(Controller::class, [$resource])
            ->getDtoClass();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetDtoClassWorksAsExpectedWithGivenDtoClasses(): void
    {
        /**
         * @var MockObject|RestDtoInterface $dtoClass
         * @var MockObject|RestResourceInterface $resource
         * @var MockObject|Controller $controller
         */
        $dtoClass = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $resource = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $controller = $this->getMockForAbstractClass(Controller::class, [$resource]);

        $dtoClasses = [
            'foo' => get_class($dtoClass),
        ];

        $reflection = new ReflectionProperty(get_class($controller), 'dtoClasses');
        $reflection->setAccessible(true);
        $reflection->setValue(null, $dtoClasses);

        static::assertSame(get_class($dtoClass), $controller->getDtoClass('foo'));
    }
}
