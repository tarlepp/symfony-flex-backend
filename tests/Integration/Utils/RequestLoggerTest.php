<?php
declare(strict_types=1);
/**
 * /tests/Integration/Utils/RequestLoggerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Utils;

use App\Resource\LogRequestResource;
use App\Utils\RequestLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RequestLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLoggerTest extends KernelTestCase
{
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LogRequestResource $resource
         */
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();

        $resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($resource))
            ->setResponse(new Response())
            ->handle();
    }

    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LogRequestResource $resource
         */
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();

        $resource
            ->expects(static::never())
            ->method('save');

        (new RequestLogger($resource))
            ->setRequest(new Request())
            ->handle();
    }

    public function testThatResourceSaveMethodIsCalled(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LogRequestResource $resource
         */
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();

        $request = new Request();
        $response = new Response();

        $resource
            ->expects(static::once())
            ->method('save');

        (new RequestLogger($resource))
            ->setRequest($request)
            ->setResponse($response)
            ->setMasterRequest(true)
            ->setUser()
            ->setApiKey()
            ->handle();
    }

    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface    $logger
         * @var \PHPUnit_Framework_MockObject_MockObject|LogRequestResource $resource
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $resource = $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();

        $request = new Request();
        $response = new Response();
        $exception = new \Exception('test exception');

        $resource
            ->expects(static::once())
            ->method('save')
            ->willThrowException($exception);

        $logger
            ->expects(static::once())
            ->method('error')
            ->with('test exception');

        (new RequestLogger($resource))
            ->setLogger($logger)
            ->setRequest($request)
            ->setResponse($response)
            ->setMasterRequest(true)
            ->setUser()
            ->setApiKey()
            ->handle();
    }
}
