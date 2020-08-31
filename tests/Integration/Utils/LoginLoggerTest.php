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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLoggerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatSetUserCallsRepositoryMethodIfWrongUserProvided(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Could not get request from current request stack');

        /**
         * @var MockObject|LogLoginResource $logLoginResource
         */
        $logLoginResource = $this->getMockBuilder(LogLoginResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new LoginLogger($logLoginResource, new RequestStack()))
            ->process('');
    }

    /**
     * @throws Throwable
     */
    public function testThatExceptionIsThrownIfRequestIsNotAvailable(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Could not get request from current request stack');

        /**
         * @var MockObject|LogLoginResource $logLoginResource
         */
        $logLoginResource = $this->getMockBuilder(LogLoginResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new LoginLogger($logLoginResource, new RequestStack()))
            ->process('');
    }
}
