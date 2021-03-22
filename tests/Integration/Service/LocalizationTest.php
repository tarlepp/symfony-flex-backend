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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use UnexpectedValueException;

/**
 * Class LocalizationTest
 *
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocalizationTest extends KernelTestCase
{
    private MockObject | CacheInterface | string $cache = '';
    private MockObject | LoggerInterface | string $logger = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    /**
     * @testdox Test that `LoggerInterface::error` method is called when `CacheInterface::get` throws an exception
     */
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        $this->getCacheMock()
            ->expects(static::once())
            ->method('get')
            ->willThrowException($exception);

        $this->getLoggerMock()
            ->expects(static::once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        (new Localization($this->getCache(), $this->getLogger()))
            ->getTimezones();
    }

    private function getCache(): CacheInterface
    {
        return $this->cache instanceof CacheInterface
            ? $this->cache
            : throw new UnexpectedValueException('Cache not set');
    }

    private function getCacheMock(): MockObject
    {
        return $this->cache instanceof MockObject
            ? $this->cache
            : throw new UnexpectedValueException('Cache not set');
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
}
