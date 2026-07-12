<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Validator/Constraints/LocaleTest.php
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\Locale;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LocaleTest extends KernelTestCase
{
    #[TestDox('Test that `getTargets` method returns expected')]
    public function testThatGetTargetsReturnsExpected(): void
    {
        self::assertSame('property', new Locale()->getTargets());
    }
}
