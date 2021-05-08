<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Service/LocalizationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Service;

use App\Service\Localization;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class LocalizationTest
 *
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocalizationTest extends KernelTestCase
{
    /**
     * @testdox Test that `LoggerInterface::error` method is called when `CacheInterface::get` throws an exception
     */
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        [$cacheMock, $loggerMock] = $this->getMocks();

        $cacheMock
            ->expects(static::once())
            ->method('get')
            ->willThrowException($exception);

        $loggerMock
            ->expects(static::once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        (new Localization($cacheMock, $loggerMock))
            ->getTimezones();
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&CacheInterface,
     *      1: \PHPUnit\Framework\MockObject\MockObject&LoggerInterface,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(CacheInterface::class)->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->getMock(),
        ];
    }
}
