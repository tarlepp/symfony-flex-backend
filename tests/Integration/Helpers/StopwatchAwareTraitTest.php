<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Helpers/StopwatchAwareTraitTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Helpers;

use App\Tests\Integration\Helpers\src\StopwatchAwareService;
use App\Utils\Tests\PhpUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

/**
 * Class StopwatchAwareTraitTest
 *
 * @package App\Tests\Integration\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class StopwatchAwareTraitTest extends KernelTestCase
{
    /**
     * @testdox Test that `stopwatch` property exists when using `StopwatchAwareTrait` trait
     */
    public function testThatStopwatchAttributeExists(): void
    {
        static::assertClassHasAttribute('stopwatch', StopwatchAwareService::class);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `stopwatch` property is instance of `Stopwatch` when using `StopwatchAwareTrait` trait
     */
    public function testThatStopwatchIsInstanceOfLoggerInterface(): void
    {
        static::bootKernel();

        /** @var StopwatchAwareService $service */
        $service = static::$container->get(StopwatchAwareService::class);

        $stopwatch = PhpUnitUtil::getProperty('stopwatch', $service);

        static::assertInstanceOf(Stopwatch::class, $stopwatch);
    }
}
