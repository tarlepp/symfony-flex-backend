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
    public function testThatStopwatchAttributeExists(): void
    {
        self::assertClassHasAttribute('stopwatch', StopwatchAwareService::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatStopwatchIsInstanceOfLoggerInterface(): void
    {
        self::bootKernel();

        /**
         * @var StopwatchAwareService $service
         */
        $service = self::$kernel->getContainer()->get(StopwatchAwareService::class);

        $stopwatch = PhpUnitUtil::getProperty('stopwatch', $service);

        self::assertInstanceOf(Stopwatch::class, $stopwatch);
    }
}
