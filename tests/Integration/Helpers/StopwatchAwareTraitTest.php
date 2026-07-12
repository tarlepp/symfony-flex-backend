<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Helpers/StopwatchAwareTraitTest.php
 */

namespace App\Tests\Integration\Helpers;

use App\Tests\Integration\Helpers\src\StopwatchAwareService;
use App\Tests\Utils\PhpUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;
use function property_exists;

final class StopwatchAwareTraitTest extends KernelTestCase
{
    public function testThatStopwatchAttributeExists(): void
    {
        self::assertTrue(property_exists(StopwatchAwareService::class, 'stopwatch'));
    }

    /**
     * @throws Throwable
     */
    public function testThatStopwatchIsInstanceOfLoggerInterface(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $service = $kernel->getContainer()
            ->get(StopwatchAwareService::class);
        $stopwatch = PhpUnitUtil::getProperty('stopwatch', $service);

        self::assertInstanceOf(Stopwatch::class, $stopwatch);
    }
}
