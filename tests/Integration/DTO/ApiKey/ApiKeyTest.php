<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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

/**
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
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
        $userGroups = [
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
        ];

        $entity = $this->getMockBuilder(ApiKeyEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity
            ->expects($this->once())
            ->method('setDescription')
            ->willReturn($entity);

        $entity
            ->expects($this->once())
            ->method('clearUserGroups');

        $entity
            ->expects($this->exactly(count($userGroups)))
            ->method('addUserGroup')
            ->willReturn($entity);

        new ApiKeyDto()
            ->setDescription('some description')
            ->setUserGroups($userGroups)
            ->update($entity);
    }
}
