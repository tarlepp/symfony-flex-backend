<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/User/UserTest.php
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

final class UserTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
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
        $entityMock = $this->getMockBuilder(UserEntity::class)->getMock();

        $entityMock
            ->expects($this->once())
            ->method('setPlainPassword')
            ->with('password');

        new UserDto()
            ->setPassword('password')
            ->update($entityMock);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method calls expected entity methods when `setUserGroups` method is used')]
    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroupStubs = [
            $this->createStub(UserGroupEntity::class),
            $this->createStub(UserGroupEntity::class),
        ];

        $entityMock = $this->getMockBuilder(UserEntity::class)->getMock();

        $entityMock
            ->expects($this->once())
            ->method('clearUserGroups');

        $entityMock
            ->expects($this->exactly(count($userGroupStubs)))
            ->method('addUserGroup')
            ->willReturn($entityMock);

        new UserDto()
            ->setUserGroups($userGroupStubs)
            ->update($entityMock);
    }
}
