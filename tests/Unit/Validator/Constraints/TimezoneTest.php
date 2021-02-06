<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Validator/Constraints/TimezoneTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\Timezone;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class TimezoneTest
 *
 * @package App\Tests\Unit\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TimezoneTest extends KernelTestCase
{
    /**
     * @testdox Test that `getTargets` method returns expected
     */
    public function testThatGetTargetsReturnsExpected(): void
    {
        static::assertSame('property', (new Timezone())->getTargets());
    }
}
