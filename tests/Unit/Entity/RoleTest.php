<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/RoleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Role;
use App\Entity\UserGroup;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTest extends KernelTestCase
{
    #[TestDox('Test that `Role::getUserGroups` returns expected')]
    public function testThatGetUserGroupsWorksLikeExpected(): void
    {
        $userGroup = new UserGroup()
            ->setName('some name');

        $role = new Role('some role');
        $role->getUserGroups()->add($userGroup);

        self::assertTrue($role->getUserGroups()->contains($userGroup));
    }
}
