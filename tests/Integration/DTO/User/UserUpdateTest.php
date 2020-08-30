<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserUpdateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserUpdate;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserUpdateTest
 *
 * @package App\Tests\Integration\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserUpdateTest extends DtoTestCase
{
    protected string $dtoClass = UserUpdate::class;

    public function testThatUserGroupsAreExpected(): void
    {
        $userGroup1 = (new UserGroup())
            ->setName('Group 1')
            ->setRole(new Role('Role 1'));

        $userGroup2 = (new UserGroup())
            ->setName('Group 2')
            ->setRole(new Role('Role 2'));

        $user = (new User())
            ->setUsername('username')
            ->addUserGroup($userGroup1);

        $dto = (new UserUpdate())
            ->load($user)
            ->setUserGroups([$userGroup2]);

        $updatedUser = $dto->update($user);

        static::assertCount(1, $updatedUser->getUserGroups());
    }
}
