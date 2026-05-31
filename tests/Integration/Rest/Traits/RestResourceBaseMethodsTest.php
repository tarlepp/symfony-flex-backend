<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/RestResourceBaseMethodsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Rest\RestResource;
use Override;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @package App\Tests\Integration\Rest\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class RestResourceBaseMethodsTest extends TestCase
{
    #[TestDox('Test that `save` calls lifecycle hooks around repository save in expected order')]
    public function testThatSaveCallsLifecycleHooksInExpectedOrder(): void
    {
        $entity = self::createStub(EntityInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);
        $order = [];

        $repository
            ->expects($this->once())
            ->method('save')
            ->with($entity, true)
            ->willReturnCallback(static function () use (&$order, $repository): BaseRepositoryInterface {
                $order[] = 'repository-save';

                return $repository;
            });

        $resource = new class($repository, $order) extends RestResource {
            /** @param array<int, string> $order */
            public function __construct(BaseRepositoryInterface $repository, public array &$order)
            {
                parent::__construct($repository);
            }

            #[Override]
            public function beforeSave(EntityInterface $entity): void
            {
                parent::beforeSave($entity);

                $this->order[] = 'before-save';
            }

            #[Override]
            public function afterSave(EntityInterface $entity): void
            {
                parent::afterSave($entity);

                $this->order[] = 'after-save';
            }
        };

        $resource->save($entity, skipValidation: true);

        self::assertSame(['before-save', 'repository-save', 'after-save'], $order);
    }

    #[TestDox('Test that `delete` passes by-reference id changes from `beforeDelete` to `afterDelete`')]
    public function testThatDeletePassesModifiedIdFromBeforeDeleteToAfterDelete(): void
    {
        $entity = self::createStub(EntityInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('find')
            ->with('raw-id')
            ->willReturn($entity);

        $repository
            ->expects($this->once())
            ->method('remove')
            ->with($entity, true)
            ->willReturnSelf();

        $resource = new class($repository) extends RestResource {
            public string $capturedIdInAfterDelete = '';

            #[Override]
            public function beforeDelete(string &$id, EntityInterface $entity): void
            {
                parent::beforeDelete($id, $entity);

                $id = 'normalized-id';
            }

            #[Override]
            public function afterDelete(string &$id, EntityInterface $entity): void
            {
                parent::afterDelete($id, $entity);

                $this->capturedIdInAfterDelete = $id;
            }
        };

        self::assertSame($entity, $resource->delete('raw-id'));
        self::assertSame('normalized-id', $resource->capturedIdInAfterDelete);
    }

    #[TestDox('Test that `update` uses lifecycle hooks and propagates by-reference id to `afterUpdate`')]
    public function testThatUpdateLifecycleHooksAreCalledAndModifiedIdPropagatesToAfterUpdate(): void
    {
        $entity = self::createStub(EntityInterface::class);
        $dto = $this->createMock(RestDtoInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->exactly(2))
            ->method('find')
            ->with('raw-id')
            ->willReturn($entity);

        $repository
            ->expects($this->once())
            ->method('save')
            ->with($entity, true)
            ->willReturnSelf();

        $dto
            ->expects($this->once())
            ->method('update')
            ->with($entity)
            ->willReturn($entity);

        $resource = new class($repository, $dto) extends RestResource {
            public string $capturedAfterUpdateId = '';
            public string $capturedGetDtoForEntityId = '';

            public function __construct(
                BaseRepositoryInterface $repository,
                private readonly RestDtoInterface $restDto,
            ) {
                parent::__construct($repository);
            }

            #[Override]
            public function getDtoForEntity(
                string $id,
                string $dtoClass,
                RestDtoInterface $dto,
                ?bool $patch = null
            ): RestDtoInterface {
                parent::getDtoForEntity($id, $dtoClass, $dto, $patch);

                $this->capturedGetDtoForEntityId = $id;

                return $this->restDto;
            }

            #[Override]
            public function beforeUpdate(string &$id, RestDtoInterface $restDto, EntityInterface $entity): void
            {
                parent::beforeUpdate($id, $restDto, $entity);

                $id = 'normalized-id';
            }

            #[Override]
            public function afterUpdate(string &$id, RestDtoInterface $restDto, EntityInterface $entity): void
            {
                parent::afterUpdate($id, $restDto, $entity);

                $this->capturedAfterUpdateId = $id;
            }
        };

        self::assertSame($entity, $resource->update('raw-id', $dto, skipValidation: true));
        self::assertSame('raw-id', $resource->capturedGetDtoForEntityId);
        self::assertSame('normalized-id', $resource->capturedAfterUpdateId);
    }

    #[TestDox('Test that `patch` lifecycle hooks are called and `getDtoForEntity` receives patch flag true')]
    public function testThatPatchLifecycleHooksAreCalledAndGetDtoForEntityReceivesPatchFlag(): void
    {
        $entity = self::createStub(EntityInterface::class);
        $dto = $this->createMock(RestDtoInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->exactly(2))
            ->method('find')
            ->with('raw-id')
            ->willReturn($entity);

        $repository
            ->expects($this->once())
            ->method('save')
            ->with($entity, true)
            ->willReturnSelf();

        $dto
            ->expects($this->once())
            ->method('update')
            ->with($entity)
            ->willReturn($entity);

        $resource = new class($repository, $dto) extends RestResource {
            public string $capturedAfterPatchId = '';
            public bool $capturedPatchFlag = false;

            public function __construct(
                BaseRepositoryInterface $repository,
                private readonly RestDtoInterface $restDto,
            ) {
                parent::__construct($repository);
            }

            #[Override]
            public function getDtoForEntity(
                string $id,
                string $dtoClass,
                RestDtoInterface $dto,
                ?bool $patch = null
            ): RestDtoInterface {
                parent::getDtoForEntity($id, $dtoClass, $dto, $patch);

                $this->capturedPatchFlag = (bool) $patch;

                return $this->restDto;
            }

            #[Override]
            public function beforePatch(string &$id, RestDtoInterface $restDto, EntityInterface $entity): void
            {
                parent::beforePatch($id, $restDto, $entity);

                $id = 'normalized-id';
            }

            #[Override]
            public function afterPatch(string &$id, RestDtoInterface $dto, EntityInterface $entity): void
            {
                parent::afterPatch($id, $dto, $entity);

                $this->capturedAfterPatchId = $id;
            }
        };

        self::assertSame($entity, $resource->patch('raw-id', $dto, skipValidation: true));
        self::assertTrue($resource->capturedPatchFlag);
        self::assertSame('normalized-id', $resource->capturedAfterPatchId);
    }

    #[TestDox('Test that `find` lifecycle hooks can modify query arguments and returned entities by reference')]
    public function testThatFindLifecycleHooksCanModifyArgumentsAndEntitiesByReference(): void
    {
        $firstEntity = self::createStub(EntityInterface::class);
        $secondEntity = self::createStub(EntityInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findByAdvanced')
            ->with(['isActive' => true], ['username' => 'ASC'], 1, 2, ['or' => ['term']])
            ->willReturn([$firstEntity]);

        $resource = new class($repository, $secondEntity) extends RestResource {
            public function __construct(
                BaseRepositoryInterface $repository,
                private readonly EntityInterface $secondEntity,
            ) {
                parent::__construct($repository);
            }

            #[Override]
            public function beforeFind(
                array &$criteria,
                array &$orderBy,
                ?int &$limit,
                ?int &$offset,
                array &$search
            ): void {
                parent::beforeFind($criteria, $orderBy, $limit, $offset, $search);

                if ($criteria === ['keep-null' => true]) {
                    $limit = null;
                    $offset = null;

                    return;
                }

                $criteria = ['isActive' => true];
                $orderBy = ['username' => 'ASC'];
                $limit = 1;
                $offset = 2;
                $search = ['or' => ['term']];
            }

            #[Override]
            public function afterFind(
                array &$criteria,
                array &$orderBy,
                ?int &$limit,
                ?int &$offset,
                array &$search,
                array &$entities
            ): void {
                parent::afterFind($criteria, $orderBy, $limit, $offset, $search, $entities);

                $entities[] = $this->secondEntity;
            }
        };

        self::assertSame([$firstEntity, $secondEntity], $resource->find());
    }

    #[TestDox('Test that `count` lifecycle hooks can modify criteria/search/count by reference')]
    public function testThatCountLifecycleHooksCanModifyArgumentsByReference(): void
    {
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('countAdvanced')
            ->with(['status' => 'active'], ['or' => ['term']])
            ->willReturn(10);

        $resource = new class($repository) extends RestResource {
            #[Override]
            public function beforeCount(array &$criteria, array &$search): void
            {
                parent::beforeCount($criteria, $search);

                $criteria = ['status' => 'active'];
                $search = ['or' => ['term']];
            }

            #[Override]
            public function afterCount(array &$criteria, array &$search, int &$count): void
            {
                parent::afterCount($criteria, $search, $count);

                $count += 5;
            }
        };

        self::assertSame(15, $resource->count());
    }

    #[TestDox('Test that `findOne` lifecycle hooks can modify id by reference before repository lookup')]
    public function testThatFindOneLifecycleHookCanModifyIdByReferenceBeforeRepositoryLookup(): void
    {
        $expectedEntity = self::createStub(EntityInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findAdvanced')
            ->with('normalized-id')
            ->willReturn($expectedEntity);

        $resource = new class($repository) extends RestResource {
            public string $capturedIdInAfterFindOne = '';

            #[Override]
            public function beforeFindOne(string &$id): void
            {
                parent::beforeFindOne($id);

                $id = 'normalized-id';
            }

            #[Override]
            public function afterFindOne(string &$id, ?EntityInterface $entity = null): void
            {
                parent::afterFindOne($id, $entity);

                $this->capturedIdInAfterFindOne = $id;
            }
        };

        self::assertSame($expectedEntity, $resource->findOne('raw-id'));
        self::assertSame('normalized-id', $resource->capturedIdInAfterFindOne);
    }

    #[TestDox(
        'Test that `findOneBy` lifecycle hooks can modify criteria/orderBy and pass expected values to `afterFindOneBy`'
    )]
    public function testThatFindOneByLifecycleHooksCanModifyArgumentsByReference(): void
    {
        $expectedEntity = self::createStub(EntityInterface::class);
        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'], ['createdAt' => 'DESC'])
            ->willReturn($expectedEntity);

        $resource = new class($repository) extends RestResource {
            /** @var array<int, mixed> */
            public array $afterFindOneByArguments = [];

            #[Override]
            public function beforeFindOneBy(array &$criteria, array &$orderBy): void
            {
                parent::beforeFindOneBy($criteria, $orderBy);

                $criteria = ['email' => 'user@example.com'];
                $orderBy = ['createdAt' => 'DESC'];
            }

            #[Override]
            public function afterFindOneBy(array &$criteria, array &$orderBy, ?EntityInterface $entity): void
            {
                parent::afterFindOneBy($criteria, $orderBy, $entity);

                $this->afterFindOneByArguments = [$criteria, $orderBy, $entity];
            }
        };

        self::assertSame($expectedEntity, $resource->findOneBy([], []));
        self::assertSame(
            [
                ['email' => 'user@example.com'],
                ['createdAt' => 'DESC'],
                $expectedEntity,
            ],
            $resource->afterFindOneByArguments,
        );
    }

    #[TestDox('Test that `getIds` passes lifecycle arguments to `afterIds` in expected order')]
    public function testThatGetIdsPassesLifecycleArgumentsToAfterIdsInExpectedOrder(): void
    {
        $criteria = ['criteria' => 'value'];
        $search = ['search' => 'value'];
        $expectedIds = ['id-1'];

        $repository = $this->createMock(BaseRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findIds')
            ->with($criteria, $search)
            ->willReturn($expectedIds);

        $resource = new class($repository) extends RestResource {
            /** @var array<int, array<mixed>> */
            public array $afterIdsArguments = [];

            /**
             * @param array<array-key, mixed> $criteria
             * @param array<array-key, mixed> $search
             * @param array<array-key, string> $ids
             */
            #[Override]
            public function afterIds(array &$criteria, array &$search, array &$ids): void
            {
                parent::afterIds($criteria, $search, $ids);

                $this->afterIdsArguments = [$criteria, $search, $ids];
            }
        };

        self::assertSame($expectedIds, $resource->getIds($criteria, $search));
        self::assertSame([$criteria, $search, $expectedIds], $resource->afterIdsArguments);
    }
}
