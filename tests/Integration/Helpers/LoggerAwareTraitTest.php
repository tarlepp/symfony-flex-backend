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
    /**
     * @testdox Test that `logger` property exists when using `LoggerAwareTrait` trait
     */
    public function testThatLoggerPropertyExists(): void
    {
        static::assertClassHasAttribute('logger', LoggerAwareService::class);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `logger` property is instance of `LoggerInterface` when using `LoggerAwareTrait` trait
     */
    public function testThatLoggerIsInstanceOfLoggerInterface(): void
    {
        static::bootKernel();

        /** @var LoggerAwareService $service */
        $service = static::$container->get(LoggerAwareService::class);

        $logger = PhpUnitUtil::getProperty('logger', $service);

        static::assertInstanceOf(LoggerInterface::class, $logger);
    }
}
