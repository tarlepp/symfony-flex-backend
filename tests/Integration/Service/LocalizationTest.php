<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Service/LocalizationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Service;

use App\Service\Localization;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class LocalizationTest
 *
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LocalizationTest extends KernelTestCase
{
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        /**
         * @var MockObject|CacheInterface $cache
         * @var MockObject|LoggerInterface $logger
         */
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $exception = new Exception('test exception');

        $cache
            ->expects(static::once())
            ->method('get')
            ->willThrowException($exception);

        $logger
            ->expects(static::once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        (new Localization($cache, $logger))
            ->getTimezones();
    }
}
