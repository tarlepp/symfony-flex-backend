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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class VersionTest
 *
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class VersionTest extends KernelTestCase
{
    /**
     * @var MockObject|CacheInterface
     */
    private $cache;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @testdox Test that `LoggerInterface::error` method is called when `CacheInterface::get` throws an exception
     */
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        $this->cache
            ->expects(static::once())
            ->method('get')
            ->willThrowException($exception);

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        (new Version('', $this->cache, $this->logger))
            ->get();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }
}
