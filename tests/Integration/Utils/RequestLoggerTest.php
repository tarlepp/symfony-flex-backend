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

/**
 * Class RequestLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLoggerTest extends KernelTestCase
{
    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|LogRequestResource
     */
    private $resource;

    /**
     * @testdox Test that log is not created if `Request` and `Response` object are not set
     */
    public function testThatLogIsNotCreatedIfRequestAndResponseObjectsAreNotSet(): void
    {
        $this->resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($this->resource, $this->logger, []))
            ->handle();
    }

    /**
     * @testdox Test that log is not created if `Request` object is not set
     */
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        $this->resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($this->resource, $this->logger, []))
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @testdox Test that log is not created if `Response` object is not set
     */
    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        $this->resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($this->resource, $this->logger, []))
            ->setRequest(new Request())
            ->handle();
    }

    /**
     * @testdox Test that log is created when `Request` and `Response` object are set
     */
    public function testThatResourceSaveMethodIsCalled(): void
    {
        $this->resource
            ->expects(static::once())
            ->method('save')
            ->with();

        (new RequestLogger($this->resource, $this->logger, []))
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @testdox Test that `LoggerInterface::error` method is called when exception is thrown
     */
    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        $this->resource
            ->expects(static::once())
            ->method('save')
            ->willThrowException(new Exception('test exception'));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('test exception');

        (new RequestLogger($this->resource, $this->logger, []))
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
    }
}
