<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/LoginLoggerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Utils;

use App\Resource\LogLoginResource;
use App\Utils\LoginLogger;
use BadMethodCallException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Class LoginLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoginLoggerTest extends KernelTestCase
{
    /**
     * @var MockObject|LogLoginResource
     */
    private $logLoginResource;

    /**
     * @throws Throwable
     *
     * @testdox Test that exception is thrown if request stack does not contain request at all
     */
    public function testThatExceptionIsThrownIfRequestIsNotAvailable(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Could not get request from current request stack');

        (new LoginLogger($this->logLoginResource, new RequestStack()))
            ->process('');
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `createEntry` method calls expected resource service method
     */
    public function testThatCreateEntryCallsResourceSaveMethod(): void
    {
        $this->logLoginResource
            ->expects(static::once())
            ->method('save');

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        (new LoginLogger($this->logLoginResource, $requestStack))
            ->process('');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logLoginResource = $this->getMockBuilder(LogLoginResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
