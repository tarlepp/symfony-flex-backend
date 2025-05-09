<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserPatchTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserPatch;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Tests\Integration\TestCase\DtoTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @package App\Tests\Integration\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserPatchTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<UserPatch>
     */
    protected static string $dtoClass = UserPatch::class;

    #[TestDox('Test that `setUserGroups` method updates entity correctly')]
    public function testThatUserGroupsAreExpected(): void
    {
        $userGroup1 = new UserGroup()
            ->setName('Group 1')
            ->setRole(new Role('Role 1'));

        $userGroup2 = new UserGroup()
            ->setName('Group 2')
            ->setRole(new Role('Role 2'));

        $user = new User()
            ->setUsername('username')
            ->addUserGroup($userGroup1);

        $dto = new UserPatch()->load($user)
            ->setUserGroups([$userGroup2]);

        /** @var User $updatedUser */
        $updatedUser = $dto->update($user);

        self::assertCount(2, $updatedUser->getUserGroups());
        self::assertSame($userGroup1, $updatedUser->getUserGroups()[0]);
        self::assertSame($userGroup2, $updatedUser->getUserGroups()[1]);
    }
}
