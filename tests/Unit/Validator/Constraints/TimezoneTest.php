<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Validator/Constraints/TimezoneTest.php
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\Timezone;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TimezoneTest extends KernelTestCase
{
    #[TestDox('Test that `getTargets` method returns expected')]
    public function testThatGetTargetsReturnsExpected(): void
    {
        self::assertSame('property', new Timezone()->getTargets());
    }
}
