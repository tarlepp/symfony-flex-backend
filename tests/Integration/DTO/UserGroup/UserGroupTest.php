<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroup;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTest extends DtoTestCase
{
    /**
     * @var class-string
     */
    protected string $dtoClass = UserGroup::class;

    /**
     * @testdox Test that `load` method actually loads entity data correctly
     */
    public function testThatLoadCallsExpectedEntityMethods(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = (new UserGroupEntity())
            ->setName('test user group')
            ->setRole($roleEntity);

        $dto = (new UserGroup())
            ->load($userGroupEntity);

        static::assertSame('test user group', $dto->getName());
        static::assertSame($roleEntity, $dto->getRole());
    }

    /**
     * @testdox Test that `update` method trigger expected entity method calls
     */
    public function testThatUpdateMethodCallsExpectedEntityMethods(): void
    {
        $roleEntity = new RoleEntity('test role');

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

        (new UserGroup())
            ->setName('test name')
            ->setRole($roleEntity)
            ->update($userGroupEntity);
    }
}
