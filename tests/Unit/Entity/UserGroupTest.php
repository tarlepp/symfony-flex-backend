<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Entity/UserGroupTest.php
 */

namespace App\Tests\Unit\Entity;

use App\Entity\UserGroup;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserGroupTest extends KernelTestCase
{
    #[TestDox('Test that `UserGroup::__toString` method returns expected')]
    public function testThatToStringMethodReturnsExpected(): void
    {
        self::assertSame(UserGroup::class, (string)(new UserGroup()));
    }
}
