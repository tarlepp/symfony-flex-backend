<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Helpers/LoggerAwareTraitTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Helpers;

use App\Tests\Integration\Helpers\src\LoggerAwareService;
use App\Utils\Tests\PhpUnitUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class LoggerAwareTraitTest
 *
 * @package App\Tests\Integration\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoggerAwareTraitTest extends KernelTestCase
{
    public function testThatLoggerAttributeExists(): void
    {
        self::assertClassHasAttribute('logger', LoggerAwareService::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoggerIsInstanceOfLoggerInterface(): void
    {
        self::bootKernel();

        /** @var LoggerAwareService $service */
        $service = self::$kernel->getContainer()->get(LoggerAwareService::class);

        $logger = PhpUnitUtil::getProperty('logger', $service);

        self::assertInstanceOf(LoggerInterface::class, $logger);
    }
}
