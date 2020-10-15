<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\UserGroup;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTest extends KernelTestCase
{
    /**
     * @testdox Test that `UserGroup::__toString` method returns expected
     */
    public function testThatToStringMethodReturnsExpected(): void
    {
        static::assertSame(UserGroup::class, (string)(new UserGroup()));
    }
}
