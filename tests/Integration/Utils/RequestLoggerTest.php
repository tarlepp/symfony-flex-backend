<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/RequestLoggerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Utils;

use App\Resource\LogRequestResource;
use App\Utils\RequestLogger;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Class RequestLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLoggerTest extends KernelTestCase
{
    private MockObject | LoggerInterface | null $logger = null;
    private MockObject | LogRequestResource | null $resource = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @testdox Test that log is not created if `Request` and `Response` object are not set
     */
    public function testThatLogIsNotCreatedIfRequestAndResponseObjectsAreNotSet(): void
    {
        $this->getResourceMock()
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($this->getResource(), $this->getLogger(), []))
            ->handle();
    }

    /**
     * @testdox Test that log is not created if `Request` object is not set
     */
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        $this->getResourceMock()
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($this->getResource(), $this->getLogger(), []))
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @testdox Test that log is not created if `Response` object is not set
     */
    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        $this->getResourceMock()
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($this->getResource(), $this->getLogger(), []))
            ->setRequest(new Request())
            ->handle();
    }

    /**
     * @testdox Test that log is created when `Request` and `Response` object are set
     */
    public function testThatResourceSaveMethodIsCalled(): void
    {
        $this->getResourceMock()
            ->expects(static::once())
            ->method('save')
            ->with();

        (new RequestLogger($this->getResource(), $this->getLogger(), []))
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @testdox Test that `LoggerInterface::error` method is called when exception is thrown
     */
    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        $this->getResourceMock()
            ->expects(static::once())
            ->method('save')
            ->willThrowException(new Exception('test exception'));

        $this->getLoggerMock()
            ->expects(static::once())
            ->method('error')
            ->with('test exception');

        (new RequestLogger($this->getResource(), $this->getLogger(), []))
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger instanceof LoggerInterface
            ? $this->logger
            : throw new UnexpectedValueException('Logger not set');
    }

    private function getLoggerMock(): MockObject
    {
        return $this->logger instanceof MockObject
            ? $this->logger
            : throw new UnexpectedValueException('Logger not set');
    }

    private function getResource(): LogRequestResource
    {
        return $this->resource instanceof LogRequestResource
            ? $this->resource
            : throw new UnexpectedValueException('Resource not set');
    }

    private function getResourceMock(): MockObject
    {
        return $this->resource instanceof MockObject
            ? $this->resource
            : throw new UnexpectedValueException('Resource not set');
    }
}
