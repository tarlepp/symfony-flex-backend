<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyUpdateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKeyUpdate;
use App\Entity\ApiKey;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class ApiKeyUpdateTest
 *
 * @package App\Tests\Integration\DTO\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUpdateTest extends DtoTestCase
{
    protected string $dtoClass = ApiKeyUpdate::class;

    public function testThatUserGroupsAreExpected(): void
    {
        $userGroup1 = (new UserGroup())
            ->setName('Group 1')
            ->setRole(new Role('Role 1'));

        $userGroup2 = (new UserGroup())
            ->setName('Group 2')
            ->setRole(new Role('Role 2'));

        $user = (new ApiKey())
            ->setDescription('description')
            ->addUserGroup($userGroup1);

        $dto = (new ApiKeyUpdate())
            ->load($user)
            ->setUserGroups([$userGroup2]);

        $updatedApiKey = $dto->update($user);

        static::assertCount(1, $updatedApiKey->getUserGroups());
    }
}
