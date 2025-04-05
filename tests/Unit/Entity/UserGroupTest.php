<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\UserGroup;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserGroupTest extends KernelTestCase
{
    #[TestDox('Test that `UserGroup::__toString` method returns expected')]
    public function testThatToStringMethodReturnsExpected(): void
    {
        self::assertSame(UserGroup::class, (string)(new UserGroup()));
    }
}
