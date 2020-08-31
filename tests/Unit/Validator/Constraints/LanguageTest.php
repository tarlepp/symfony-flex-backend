<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Validator/Constraints/LanguageTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\Language;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LanguageTest
 *
 * @package App\Tests\Unit\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LanguageTest extends KernelTestCase
{
    public function testThatGetTargetsReturnsExpected(): void
    {
        static::assertSame('property', (new Language())->getTargets());
    }
}
