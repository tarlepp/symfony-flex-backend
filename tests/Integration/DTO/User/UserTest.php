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
use App\Tests\Integration\DTO\DtoTestCase;
use Throwable;
use function count;

/**
 * Class UserTest
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserTest extends DtoTestCase
{
    /**
     * @var class-string
     */
    protected string $dtoClass = UserDto::class;

    /**
     * @testdox Test that `load` method actually loads entity data correctly
     */
    public function testThatLoadMethodWorks(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = (new UserGroupEntity())
            ->setName('test user group')
            ->setRole($roleEntity);

        // Create User entity
        $userEntity = (new UserEntity())
            ->setUsername('username')
            ->setFirstName('first name')
            ->setLastName('last name')
            ->setEmail('firstname.surname@test.com')
            ->addUserGroup($userGroupEntity);

        $dto = (new UserDto())
            ->load($userEntity);

        static::assertSame('username', $dto->getUsername());
        static::assertSame('first name', $dto->getFirstName());
        static::assertSame('last name', $dto->getLastName());
        static::assertSame('firstname.surname@test.com', $dto->getEmail());
        static::assertSame([$userGroupEntity], $dto->getUserGroups());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `update` method calls `setPlainPassword` entity method when `password` is set to DTO
     */
    public function testThatUpdateMethodCallsExpectedEntityMethodIfPasswordIsVisited(): void
    {
        $entity = $this->getMockBuilder(UserEntity::class)->getMock();

        $entity
            ->expects(static::once())
            ->method('setPlainPassword')
            ->with('password');

        (new UserDto())
            ->setPassword('password')
            ->update($entity);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `update` method calls expected entity methods when `setUserGroups` method is used
     */
    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroups = [
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
        ];

        $entity = $this->getMockBuilder(UserEntity::class)->getMock();

        $entity
            ->expects(static::once())
            ->method('clearUserGroups');

        $entity
            ->expects(static::exactly(count($userGroups)))
            ->method('addUserGroup')
            ->willReturn($entity);

        (new UserDto())
            ->setUserGroups($userGroups)
            ->update($entity);
    }
}
