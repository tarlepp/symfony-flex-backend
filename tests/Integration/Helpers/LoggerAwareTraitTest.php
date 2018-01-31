<?php
declare(strict_types=1);
/**
 * /tests/Integration/Helpers/LoggerAwareTraitTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Helpers;

use App\Tests\Integration\Helpers\src\LoggerAwareService;
use App\Utils\Tests\PHPUnitUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LoggerAwareTraitTest
 *
 * @package App\Tests\Integration\Helpers
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggerAwareTraitTest extends KernelTestCase
{
    public function testThatLoggerAttributeExists(): void
    {
        static::assertClassHasAttribute('logger', LoggerAwareService::class);
    }

    /**
     * @throws \ReflectionException
     */
    public function testThatLoggerIsInstanceOfLoggerInterface(): void
    {
        static::bootKernel();

        $service = static::$kernel->getContainer()->get(LoggerAwareService::class);

        $logger = PHPUnitUtil::getProperty('logger', $service);

        static::assertInstanceOf(LoggerInterface::class, $logger);

        unset($service, $logger);
    }
}
