<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\UserGroup;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTest extends KernelTestCase
{
    public function testThatToStringMethodReturnsExpected(): void
    {
        static::assertSame(UserGroup::class, (string)(new UserGroup()));
    }
}
