<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/DTO/UserTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\DTO;

use App\Entity\EntityInterface;
use App\Rest\DTO\User;

/**
 * Class UserTest
 *
 * @package App\Tests\Integration\Rest\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTest extends DtoTestCase
{
    protected $dtoClass = User::class;

    public function testThatUpdateMethodCallsExpectedEntityMethodIfPasswordIsVisited(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface $entity */
        $entity = $this->getMockBuilder(EntityInterface::class)
            ->setMethods(['getId', 'setPlainPassword'])
            ->getMock();

        $entity
            ->expects(static::once())
            ->method('setPlainPassword')
            ->with('password');

        /** @var User $dto */
        $dto = new $this->dtoClass();
        $dto->setPassword('password');
        $dto->update($entity);
    }

    public function testThatUpdateMethodCallsExpectedEntityMethodsIfUserGroupsIsVisited(): void
    {
        $userGroups = [
            'foo',
            'bar',
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface $entity */
        $entity = $this->getMockBuilder(EntityInterface::class)
            ->setMethods(['getId', 'clearUserGroups', 'addUserGroup'])
            ->getMock();

        $entity
            ->expects(static::once())
            ->method('clearUserGroups');

        $entity
            ->expects(static::exactly(\count($userGroups)))
            ->method('addUserGroup')
            ->willReturn($entity);

        /** @var User $dto */
        $dto = new $this->dtoClass();
        $dto->setUserGroups($userGroups);
        $dto->update($entity);
    }
}
