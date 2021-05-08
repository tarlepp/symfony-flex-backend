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
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RequestLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLoggerTest extends KernelTestCase
{
    /**
     * @testdox Test that log is not created if `Request` and `Response` object are not set
     */
    public function testThatLogIsNotCreatedIfRequestAndResponseObjectsAreNotSet(): void
    {
        [$loggerMock, $logRequestResourceMock] = $this->getMocks();

        $logRequestResourceMock
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($logRequestResourceMock, $loggerMock, []))
            ->handle();
    }

    /**
     * @testdox Test that log is not created if `Request` object is not set
     */
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        [$loggerMock, $logRequestResourceMock] = $this->getMocks();

        $logRequestResourceMock
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($logRequestResourceMock, $loggerMock, []))
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @testdox Test that log is not created if `Response` object is not set
     */
    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        [$loggerMock, $logRequestResourceMock] = $this->getMocks();

        $logRequestResourceMock
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($logRequestResourceMock, $loggerMock, []))
            ->setRequest(new Request())
            ->handle();
    }

    /**
     * @testdox Test that log is created when `Request` and `Response` object are set
     */
    public function testThatResourceSaveMethodIsCalled(): void
    {
        [$loggerMock, $logRequestResourceMock] = $this->getMocks();

        $logRequestResourceMock
            ->expects(static::once())
            ->method('save')
            ->with();

        (new RequestLogger($logRequestResourceMock, $loggerMock, []))
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @testdox Test that `LoggerInterface::error` method is called when exception is thrown
     */
    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        [$loggerMock, $logRequestResourceMock] = $this->getMocks();

        $logRequestResourceMock
            ->expects(static::once())
            ->method('save')
            ->willThrowException(new Exception('test exception'));

        $loggerMock
            ->expects(static::once())
            ->method('error')
            ->with('test exception');

        (new RequestLogger($logRequestResourceMock, $loggerMock, []))
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&LoggerInterface,
     *      1: \PHPUnit\Framework\MockObject\MockObject&LogRequestResource,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(LoggerInterface::class)->getMock(),
            $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock(),
        ];
    }
}
