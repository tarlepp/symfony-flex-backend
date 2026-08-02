<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Validator/Constraints/UniqueEmailTest.php
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\UniqueEmail;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UniqueEmailTest extends KernelTestCase
{
    #[TestDox('Test that `getTargets` method returns expected')]
    public function testThatGetTargetsReturnsExpected(): void
    {
        self::assertSame('class', new UniqueEmail()->getTargets());
    }
}
