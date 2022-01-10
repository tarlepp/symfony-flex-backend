<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Enum/LanguageTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Enum;

use App\Enum\Language;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LanguageTest
 *
 * @package App\Tests\Integration\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LanguageTest extends KernelTestCase
{
    /**
     * @testdox Test that `Language::getDefault()` returns expected name and value
     */
    public function testThatGetDefaultMethodReturnsExpected(): void
    {
        static::assertSame('EN', Language::getDefault()->name);
        static::assertSame('en', Language::getDefault()->value);
    }

    /**
     * @testdox Test that `Language::getValues()` returns expected array of values
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        static::assertSame(['en', 'fi'], Language::getValues());
    }
}
