<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/LoginLoggerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Utils;

use App\Enum\LogLogin;
use App\Resource\LogLoginResource;
use App\Utils\LoginLogger;
use BadMethodCallException;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LoginLoggerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that exception is thrown if request stack does not contain request at all')]
    public function testThatExceptionIsThrownIfRequestIsNotAvailable(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Could not get request from current request stack');

        new LoginLogger($this->getResource(), new RequestStack())
            ->process(LogLogin::SUCCESS);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `createEntry` method calls expected resource service method')]
    public function testThatCreateEntryCallsResourceSaveMethod(): void
    {
        $resource = $this->getResource();

        $resource
            ->expects($this->once())
            ->method('save');

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        new LoginLogger($resource, $requestStack)
            ->process(LogLogin::SUCCESS);
    }

    /**
     * @phpstan-return MockObject&LogLoginResource
     */
    private function getResource(): MockObject
    {
        return $this->getMockBuilder(LogLoginResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
