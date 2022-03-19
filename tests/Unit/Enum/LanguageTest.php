<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Enum/LanguageTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Enum;

use App\Enum\Language;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LanguageTest
 *
 * @package App\Tests\Unit\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LanguageTest extends KernelTestCase
{
    /**
     * @testdox Test that enum has all expected cases
     */
    public function testThatEnumCasesAreExpected(): void
    {
        self::assertSame(
            [
                Language::EN,
                Language::FI,
            ],
            Language::cases()
        );
    }

    /**
     * @testdox Test that `Language::getValues()` method returns expected value
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        self::assertSame(['en', 'fi'], Language::getValues());
    }

    /**
     * @testdox Test that `Language::getDefault()` method returns expected value
     */
    public function testThatGetDefaultMethodReturnsExpected(): void
    {
        self::assertSame(Language::EN, Language::getDefault());
    }
}
