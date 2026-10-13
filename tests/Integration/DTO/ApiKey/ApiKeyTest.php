<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/ApiKey/ApiKeyTest.php
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKey as ApiKeyDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Tests\Integration\TestCase\DtoTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function count;

final class ApiKeyTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
     * @phpstan-var class-string<ApiKeyDto>
     */
    protected static string $dtoClass = ApiKeyDto::class;

    #[TestDox('Test that `load` method actually loads entity data correctly')]
    public function testThatLoadMethodWorks(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = new UserGroupEntity()
            ->setName('test user group')
            ->setRole($roleEntity);

        // Create ApiKey entity
        $apiKeyEntity = new ApiKeyEntity()
            ->setDescription('Some description')
            ->addUserGroup($userGroupEntity);

        $dto = new ApiKeyDto()
            ->load($apiKeyEntity);

        self::assertSame('Some description', $dto->getDescription());
        self::assertSame([$userGroupEntity], $dto->getUserGroups());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method calls expected entity methods when `setUserGroups` method is used')]
    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroupStubs = [
            self::createStub(UserGroupEntity::class),
            self::createStub(UserGroupEntity::class),
        ];

        $entityMock = $this->getMockBuilder(ApiKeyEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityMock
            ->expects($this->once())
            ->method('setDescription')
            ->willReturn($entityMock);

        $entityMock
            ->expects($this->once())
            ->method('clearUserGroups');

        $entityMock
            ->expects($this->exactly(count($userGroupStubs)))
            ->method('addUserGroup')
            ->willReturn($entityMock);

        new ApiKeyDto()
            ->setDescription('some description')
            ->setUserGroups($userGroupStubs)
            ->update($entityMock);
    }
}
