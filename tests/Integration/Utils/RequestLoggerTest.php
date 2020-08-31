<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/RequestLoggerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
use Throwable;

/**
 * Class RequestLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLoggerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        /**
         * @var MockObject|LogRequestResource $resource
         * @var MockObject|LoggerInterface $logger
         */
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($resource, $logger, []))
            ->setResponse(new Response())
            ->handle();
    }

    /**
     * @throws Throwable
     */
    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        /**
         * @var MockObject|LogRequestResource $resource
         * @var MockObject|LoggerInterface $logger
         */
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($resource, $logger, []))
            ->setRequest(new Request())
            ->handle();
    }

    /**
     * @throws Throwable
     */
    public function testThatResourceSaveMethodIsCalled(): void
    {
        /**
         * @var MockObject|LogRequestResource $resource
         * @var MockObject|LoggerInterface $logger
         */
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $request = new Request();
        $response = new Response();

        $resource
            ->expects(static::once())
            ->method('save');

        (new RequestLogger($resource, $logger, []))
            ->setRequest($request)
            ->setResponse($response)
            ->setMasterRequest(true)
            ->setUser()
            ->setApiKey()
            ->handle();
    }

    /**
     * @throws Throwable
     */
    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        /**
         * @var MockObject|LoggerInterface $logger
         * @var MockObject|LogRequestResource $resource
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();

        $request = new Request();
        $response = new Response();
        $exception = new Exception('test exception');

        $resource
            ->expects(static::once())
            ->method('save')
            ->willThrowException($exception);

        $logger
            ->expects(static::once())
            ->method('error')
            ->with('test exception');

        (new RequestLogger($resource, $logger, []))
            ->setRequest($request)
            ->setResponse($response)
            ->setMasterRequest(true)
            ->setUser()
            ->setApiKey()
            ->handle();
    }
}
