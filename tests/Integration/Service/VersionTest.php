<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Service/VersionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Service;

use App\Service\Version;
use Exception;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class VersionTest extends KernelTestCase
{
    #[TestDox('Test that `LoggerInterface::error` method is called when `CacheInterface')]
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        $cache = $this->getCache();
        $logger = $this->getLogger();

        $cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        new Version('', $cache, $logger)
            ->get();
    }

    /**
     * @phpstan-return MockObject&CacheInterface
     */
    private function getCache(): MockObject
    {
        return $this->getMockBuilder(CacheInterface::class)->getMock();
    }

    /**
     * @phpstan-return MockObject&LoggerInterface
     */
    private function getLogger(): MockObject
    {
        return $this->getMockBuilder(LoggerInterface::class)->getMock();
    }
}
