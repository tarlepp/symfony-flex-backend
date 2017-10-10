<?php
declare(strict_types=1);
/**
 * /tests/Integration/DTO/UserGroupTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\DTO;

use App\DTO\UserGroup;
use App\Entity\EntityInterface;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Integration\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTest extends DtoTestCase
{
    protected $dtoClass = UserGroup::class;

    public function testThatLoadCallsExpectedEntityMethods(): void
    {
        // Create Role entity
        $roleEntity = new RoleEntity('test role');

        // Create UserGroup entity
        $userGroupEntity = new UserGroupEntity();
        $userGroupEntity->setName('test user group');
        $userGroupEntity->setRole($roleEntity);

        /** @var UserGroup $dto */
        $dto = new $this->dtoClass();
        $dto->load($userGroupEntity);

        static::assertSame('test user group', $dto->getName());
        static::assertSame($roleEntity->getId(), $dto->getRole());
    }

    public function testThatUpdateMethodCallsExpectedEntityMethods(): void
    {
        $roleEntity = new RoleEntity('test role');

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface|UserGroupEntity $userGroupEntity */
        $userGroupEntity = $this->getMockBuilder(EntityInterface::class)
            ->setMethods(['getId', 'setName', 'setRole'])
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

        /** @var UserGroup $dto */
        $dto = new $this->dtoClass();
        $dto->setName('test name');
        $dto->setRole($roleEntity);
        $dto->update($userGroupEntity);
    }
}
