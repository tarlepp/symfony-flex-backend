<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKey as ApiKeyDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Tests\Integration\DTO\DtoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;
use function count;

/**
 * Class ApiKeyTest
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyTest extends DtoTestCase
{
    protected string $dtoClass = ApiKeyDto::class;

    public function testThatLoadMethodWorks(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = (new UserGroupEntity())
            ->setName('test user group')
            ->setRole($roleEntity);

        // Create ApiKey entity
        $apiKeyEntity = (new ApiKeyEntity())
            ->setDescription('Some description')
            ->addUserGroup($userGroupEntity);

        /** @var ApiKeyDto $dto */
        $dto = (new $this->dtoClass())
            ->load($apiKeyEntity);

        static::assertSame('Some description', $dto->getDescription());
        static::assertSame([$userGroupEntity], $dto->getUserGroups());
    }

    /**
     * @throws Throwable
     */
    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroups = [
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
        ];

        /** @var MockObject|EntityInterface $entity */
        $entity = $this->getMockBuilder(ApiKeyEntity::class)
            ->getMock();

        $entity
            ->expects(static::once())
            ->method('setDescription')
            ->willReturn($entity);

        $entity
            ->expects(static::once())
            ->method('clearUserGroups');

        $entity
            ->expects(static::exactly(count($userGroups)))
            ->method('addUserGroup')
            ->willReturn($entity);

        (new $this->dtoClass())
            ->setDescription('some description')
            ->setUserGroups($userGroups)
            ->update($entity);
    }
}
