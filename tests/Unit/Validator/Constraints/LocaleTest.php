<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Validator/Constraints/LocaleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LocaleTest
 *
 * @package App\Tests\Unit\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocaleTest extends KernelTestCase
{
    /**
     * @testdox Test that `getTargets` method returns expected
     */
    public function testThatGetTargetsReturnsExpected(): void
    {
        static::assertSame('property', (new Locale())->getTargets());
    }
}
