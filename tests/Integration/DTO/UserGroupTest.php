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
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface|RoleEntity $userGroupEntity */
        $roleEntity = $this->getMockBuilder(EntityInterface::class)
            ->setMethods(['getId'])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface|UserGroupEntity $userGroupEntity */
        $userGroupEntity = $this->getMockBuilder(EntityInterface::class)
            ->setMethods(['getId', 'getName', 'getRole'])
            ->getMock();

        $userGroupEntity
            ->expects(static::once())
            ->method('getName')
            ->willReturn('test name');

        $userGroupEntity
            ->expects(static::once())
            ->method('getRole')
            ->willReturn($roleEntity);

        /** @var UserGroup $dto */
        $dto = new $this->dtoClass();
        $dto->load($userGroupEntity);

        static::assertSame('test name', $dto->getName());
        static::assertSame($roleEntity, $dto->getRole());
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
