<?php
declare(strict_types=1);
/**
 * /tests/Integration/DTO/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\DTO;

use App\DTO\ApiKey as ApiKeyDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\EntityInterface;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;
use PHPUnit\Framework\MockObject\MockObject;
use function count;

/**
 * Class ApiKeyTest
 *
 * @package App\Tests\Integration\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyTest extends DtoTestCase
{
    protected $dtoClass = ApiKeyDto::class;

    public function testThatLoadMethodWorks(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = new UserGroupEntity();
        $userGroupEntity->setName('test user group');
        $userGroupEntity->setRole($roleEntity);

        // Create ApiKey entity
        $apiKeyEntity = new ApiKeyEntity();
        $apiKeyEntity->setDescription('Some description');
        $apiKeyEntity->addUserGroup($userGroupEntity);

        /** @var ApiKeyDto $dto */
        $dto = new $this->dtoClass();
        $dto->load($apiKeyEntity);

        static::assertSame('Some description', $dto->getDescription());
        static::assertSame([$userGroupEntity->getId()], $dto->getUserGroups());

        unset($dto, $apiKeyEntity, $userGroupEntity, $roleEntity);
    }

    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroups = [
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
            $this->getMockBuilder(UserGroupEntity::class)->getMock(),
        ];

        /** @var MockObject|EntityInterface $entity */
        $entity = $this->getMockBuilder(ApiKeyEntity::class)
            ->setMethods(['getId', 'setDescription', 'clearUserGroups', 'addUserGroup'])
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

        /** @var ApiKeyDto $dto */
        $dto = new $this->dtoClass();
        $dto->setDescription('some description');
        $dto->setUserGroups($userGroups);
        $dto->update($entity);

        unset($dto, $entity, $userGroups);
    }
}
