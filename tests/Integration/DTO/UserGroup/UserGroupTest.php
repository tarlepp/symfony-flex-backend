<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroup;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Tests\Integration\DTO\DtoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTest extends DtoTestCase
{
    protected string $dtoClass = UserGroup::class;

    public function testThatLoadCallsExpectedEntityMethods(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = (new UserGroupEntity())
            ->setName('test user group')
            ->setRole($roleEntity);

        /** @var UserGroup $dto */
        $dto = (new $this->dtoClass())
            ->load($userGroupEntity);

        static::assertSame('test user group', $dto->getName());
        static::assertSame($roleEntity, $dto->getRole());
    }

    public function testThatUpdateMethodCallsExpectedEntityMethods(): void
    {
        $roleEntity = new RoleEntity('test role');

        /** @var MockObject|UserGroupEntity $userGroupEntity */
        $userGroupEntity = $this->getMockBuilder(UserGroupEntity::class)
            ->getMock();

        $userGroupEntity
            ->expects(static::once())
            ->method('setName')
            ->with('test name')
            ->willReturn($userGroupEntity);

        $userGroupEntity
            ->expects(static::once())
            ->method('setRole')
            ->with($roleEntity)
            ->willReturn($userGroupEntity);

        (new $this->dtoClass())
            ->setName('test name')
            ->setRole($roleEntity)
            ->update($userGroupEntity);
    }
}
