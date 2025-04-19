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
use App\Tests\Integration\TestCase\DtoTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<UserGroup>
     */
    protected static string $dtoClass = UserGroup::class;

    #[TestDox('Test that `load` method actually loads entity data correctly')]
    public function testThatLoadCallsExpectedEntityMethods(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = new UserGroupEntity()
            ->setName('test user group')
            ->setRole($roleEntity);

        $dto = new UserGroup()
            ->load($userGroupEntity);

        self::assertSame('test user group', $dto->getName());
        self::assertSame($roleEntity, $dto->getRole());
    }

    #[TestDox('Test that `update` method trigger expected entity method calls')]
    public function testThatUpdateMethodCallsExpectedEntityMethods(): void
    {
        $roleEntity = new RoleEntity('test role');

        $userGroupEntity = $this->getMockBuilder(UserGroupEntity::class)
            ->getMock();

        $userGroupEntity
            ->expects($this->once())
            ->method('setName')
            ->with('test name')
            ->willReturn($userGroupEntity);

        $userGroupEntity
            ->expects($this->once())
            ->method('setRole')
            ->with($roleEntity)
            ->willReturn($userGroupEntity);

        new UserGroup()
            ->setName('test name')
            ->setRole($roleEntity)
            ->update($userGroupEntity);
    }
}
