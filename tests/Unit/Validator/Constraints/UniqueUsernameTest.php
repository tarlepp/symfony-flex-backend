<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Validator/Constraints/UniqueUsernameTest.php
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\UniqueUsername;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UniqueUsernameTest extends KernelTestCase
{
    #[TestDox('Test that `getTargets` method returns expected')]
    public function testThatGetTargetsReturnsExpected(): void
    {
        self::assertSame('class', new UniqueUsername()->getTargets());
    }
}
