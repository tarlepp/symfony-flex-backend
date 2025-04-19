<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\User as UserDto;
use App\Entity\Role as RoleEntity;
use App\Entity\User as UserEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Tests\Integration\TestCase\DtoTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function count;

/**
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<UserDto>
     */
    protected static string $dtoClass = UserDto::class;

    #[TestDox('Test that `load` method actually loads entity data correctly')]
    public function testThatLoadMethodWorks(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = new UserGroupEntity()
            ->setName('test user group')
            ->setRole($roleEntity);

        // Create User entity
        $userEntity = new UserEntity()
            ->setUsername('username')
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com')
            ->addUserGroup($userGroupEntity);

        $dto = new UserDto()
            ->load($userEntity);

        self::assertSame('username', $dto->getUsername());
        self::assertSame('first name', $dto->getFirstName());
        self::assertSame('last name', $dto->getLastName());
        self::assertSame('firstname.surname@test.com', $dto->getEmail());
        self::assertSame([$userGroupEntity], $dto->getUserGroups());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method calls `setPlainPassword` entity method when `password` is set to DTO')]
    public function testThatUpdateMethodCallsExpectedEntityMethodIfPasswordIsVisited(): void
    {
        $entity = $this->getMockBuilder(UserEntity::class)->getMock();

        $entity
            ->expects($this->once())
            ->method('setPlainPassword')
            ->with('password');

        new UserDto()
            ->setPassword('password')
            ->update($entity);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method calls expected entity methods when `setUserGroups` method is used')]
    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroups = [
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
        ];

        $entity = $this->getMockBuilder(UserEntity::class)->getMock();

        $entity
            ->expects($this->once())
            ->method('clearUserGroups');

        $entity
            ->expects($this->exactly(count($userGroups)))
            ->method('addUserGroup')
            ->willReturn($entity);

        new UserDto()
            ->setUserGroups($userGroups)
            ->update($entity);
    }
}
