<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/LoginLoggerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Utils;

use App\Resource\LogLoginResource;
use App\Utils\LoginLogger;
use BadMethodCallException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Class LoginLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLoggerTest extends KernelTestCase
{
    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Could not get request from current request stack
     *
     * @throws Throwable
     */
    public function testThatSetUserCallsRepositoryMethodIfWrongUserProvided(): void
    {
        /**
         * @var MockObject|LogLoginResource $logLoginResource
         */
        $logLoginResource = $this->getMockBuilder(LogLoginResource::class)->disableOriginalConstructor()->getMock();
        $requestStack = new RequestStack();

        $loginLogger = new LoginLogger($logLoginResource, $requestStack);
        $loginLogger->process('');

        unset($loginLogger, $requestStack, $logLoginResource);
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Could not get request from current request stack
     *
     * @throws Throwable
     */
    public function testThatExceptionIsThrownIfRequestIsNotAvailable(): void
    {
        /**
         * @var MockObject|LogLoginResource $logLoginResource
         */
        $logLoginResource = $this->getMockBuilder(LogLoginResource::class)->disableOriginalConstructor()->getMock();
        $requestStack = new RequestStack();

        $loginLogger = new LoginLogger($logLoginResource, $requestStack);
        $loginLogger->process('');
    }
}
