<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Enum/LocaleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Enum;

use App\Enum\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LocaleTest
 *
 * @package App\Tests\Unit\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocaleTest extends KernelTestCase
{
    /**
     * @testdox Test that enum has all expected cases
     */
    public function testThatEnumCasesAreExpected(): void
    {
        self::assertSame(
            [
                Locale::EN,
                Locale::FI,
            ],
            Locale::cases()
        );
    }

    /**
     * @testdox Test that `Locale::getValues()` method returns expected value
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        self::assertSame(['en', 'fi'], Locale::getValues());
    }

    /**
     * @testdox Test that `Locale::getDefault()` method returns expected value
     */
    public function testThatGetDefaultMethodReturnsExpected(): void
    {
        self::assertSame(Locale::EN, Locale::getDefault());
    }
}
