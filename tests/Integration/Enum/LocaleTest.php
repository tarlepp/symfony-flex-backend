<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Enum/LocaleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Enum;

use App\Enum\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LocaleTest
 *
 * @package App\Tests\Integration\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocaleTest extends KernelTestCase
{
    /**
     * @testdox Test that `Locale::getDefault()` returns expected name and value
     */
    public function testThatGetDefaultMethodReturnsExpected(): void
    {
        static::assertSame('EN', Locale::getDefault()->name);
        static::assertSame('en', Locale::getDefault()->value);
    }

    /**
     * @testdox Test that `Locale::getValues()` returns expected array of values
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        static::assertSame(['en', 'fi'], Locale::getValues());
    }
}
