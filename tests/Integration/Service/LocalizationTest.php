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
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocalizationTest extends KernelTestCase
{
    #[TestDox('Test that `LoggerInterface::error` method is called when `CacheInterface` throws an exception')]
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        (new Localization($cache, $logger, $requestStack))
            ->getTimezones();
    }
}
