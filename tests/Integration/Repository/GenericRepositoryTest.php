<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/GenericRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\Role;
use App\Repository\ApiKeyRepository;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\RoleRepository;
use App\Resource\ApiKeyResource;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use UnexpectedValueException;

/**
 * Class GenericRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericRepositoryTest extends KernelTestCase
{
    /**
     * @var class-string<ApiKeyResource>
     */
    private string $resourceClass = ApiKeyResource::class;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetReferenceReturnsExpected(): void
    {
        $entity = new ApiKeyEntity();

        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);

        static::assertInstanceOf(ApiKeyEntity::class, $resource->getRepository()->getReference($entity->getId()));
    }

    /**
     * @throws Throwable
     */
    public function testThatGetReferenceReturnsExpectedWithNonUuidInput(): void
    {
        /** @var RoleRepository $repository */
        $repository = static::$container->get(RoleRepository::class);

        static::assertInstanceOf(Role::class, $repository->getReference('some-role'));
    }

    /**
     * @throws Throwable
     */
    public function testThatGetAssociationsReturnsExpected(): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);

        static::assertSame(
            ['userGroups', 'logsRequest', 'createdBy', 'updatedBy'],
            array_keys($resource->getRepository()->getAssociations())
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatGetClassMetaDataReturnsExpected(): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);

        static::assertInstanceOf(ClassMetadata::class, $resource->getRepository()->getClassMetaData());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityManagerThrowsAnExceptionIfManagerIsNotValid(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot get entity manager for entity \'App\Entity\ApiKey\'');

        $managerObject = $this->getMockForAbstractClass(
            AbstractManagerRegistry::class,
            [],
            '',
            false,
            true,
            true,
            ['getManagerForClass']
        );

        $managerObject
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $repository = $this->getRepositoryWithCustomManagerRegistry($managerObject);
        $repository->getEntityManager();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityManagerDoNotResetExistingManagerIfItIsOpen(): void
    {
        $managerObject = $this->getMockForAbstractClass(
            AbstractManagerRegistry::class,
            [],
            '',
            false,
            true,
            true,
            ['getManagerForClass', 'isOpen']
        );

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
            ->expects(static::once())
            ->method('isOpen')
            ->willReturn(true);

        $managerObject
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $repository = $this->getRepositoryWithCustomManagerRegistry($managerObject);

        static::assertSame($entityManager, $repository->getEntityManager());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityManagerResetManagerIfItIsNotOpen(): void
    {
        $managerObject = $this->getMockForAbstractClass(
            AbstractManagerRegistry::class,
            [],
            '',
            false,
            true,
            true,
            ['getManagerForClass', 'isOpen', 'resetManager']
        );

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
            ->expects(static::exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);

        $secondEntityManager = clone $entityManager;

        $managerObject
            ->expects(static::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($entityManager, $secondEntityManager);

        $managerObject
            ->expects(static::once())
            ->method('resetManager');

        $repository = $this->getRepositoryWithCustomManagerRegistry($managerObject);

        $actualEntityManager = $repository->getEntityManager();

        static::assertNotSame($entityManager, $actualEntityManager);
        static::assertSame($secondEntityManager, $actualEntityManager);
    }

    /**
     * @dataProvider dataProviderTestThatAddLeftJoinWorksAsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     *
     * @testdox Test that add left join works as expected, using $parameters and expecting '$expected'
     */
    public function testThatAddLeftJoinWorksAsExpected(string $expected, StringableArrayObject $parameters): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);
        $repository = $resource->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository
            ->addLeftJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @dataProvider dataProviderTestThatAddInnerJoinWorksAsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     *
     * @testdox Test that add inner join works as expected, using $parameters and expecting '$expected'
     */
    public function testThatAddInnerJoinWorksAsExpected(string $expected, StringableArrayObject $parameters): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);
        $repository = $resource->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository
            ->addInnerJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @dataProvider dataProviderTestThatAddLeftJoinWorksAsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     *
     * @testdox Test that add left join adds same join just once, using $parameters and expecting '$expected'
     */
    public function testThatAddLeftJoinAddsJoinJustOnce(string $expected, StringableArrayObject $parameters): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);
        $repository = $resource->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository
            ->addLeftJoin($parameters->getArrayCopy())
            ->addLeftJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @dataProvider dataProviderTestThatAddInnerJoinWorksAsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     *
     * @testdox Test that add inner join adds same join just once, using $parameters and expecting '$expected'
     */
    public function testThatAddInnerJoinAddsJoinJustOnce(string $expected, StringableArrayObject $parameters): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);
        $repository = $resource->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository
            ->addInnerJoin($parameters->getArrayCopy())
            ->addInnerJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @throws Throwable
     */
    public function testThatAddCallbackWorks(): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);
        $repository = $resource->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = static function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder): void {
            static::assertSame($queryBuilder, $qb);
            static::assertSame(1, $foo);
            static::assertSame('string', $bar);
        };

        $repository
            ->addCallback($callable, [1, 'string'])
            ->processQueryBuilder($queryBuilder);
    }

    /**
     * @throws Throwable
     */
    public function testThatAddCallbackCallsCallbackJustOnce(): void
    {
        /** @var ApiKeyResource $resource */
        $resource = static::$container->get($this->resourceClass);
        $repository = $resource->getRepository();

        $count = 0;

        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = static function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder, &$count): void {
            static::assertSame($queryBuilder, $qb);
            static::assertSame(1, $foo);
            static::assertSame('string', $bar);

            $count++;
        };

        // Attach same callback twice
        $repository
            ->addCallback($callable, [1, 'string'])
            ->addCallback($callable, [1, 'string']);

        // Process query builder
        $repository->processQueryBuilder($queryBuilder);

        static::assertSame(1, $count);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindMethodCallsExpectedEntityManagerMethod(): void
    {
        $arguments = [
            'id',
            null,
            null,
        ];

        [$managerRegistryMock, $entityManagerMock, ] = $this->getMocks();

        $entityManagerMock
            ->expects(static::once())
            ->method('find')
            ->with(
                ApiKeyEntity::class,
                'id',
                null,
                null
            );

        $managerRegistryMock
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManagerMock);

        $repository = $this->getRepositoryWithCustomManagerRegistry($managerRegistryMock);
        $repository->find(...$arguments);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneByMethodCallsExpectedEntityManagerMethod(): void
    {
        $arguments = [
            ['some criteria'],
            ['some order by'],
        ];

        [$managerRegistryMock, $entityManagerMock, $entityRepositoryMock] = $this->getMocks();

        $entityRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(...$arguments);

        $entityManagerMock
            ->expects(static::once())
            ->method('getRepository')
            ->with(ApiKeyEntity::class)
            ->willReturn($entityRepositoryMock);

        $managerRegistryMock
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManagerMock);

        $repository = $this->getRepositoryWithCustomManagerRegistry($managerRegistryMock);
        $repository->findOneBy(...$arguments);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindByMethodCallsExpectedEntityManagerMethod(): void
    {
        $arguments = [
            ['foo' => 'some criteria'],
            ['bar' => 'some order by'],
            10,
            20,
        ];

        [$managerRegistryMock, $entityManagerMock, $entityRepositoryMock] = $this->getMocks();

        $entityRepositoryMock
            ->expects(static::once())
            ->method('findBy')
            ->with(...$arguments)
            ->willReturn([]);

        $entityManagerMock
            ->expects(static::once())
            ->method('getRepository')
            ->with(ApiKeyEntity::class)
            ->willReturn($entityRepositoryMock);

        $managerRegistryMock
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManagerMock);

        /**
         * @var BaseRepositoryInterface $repository
         */
        $repository = $this->getRepositoryWithCustomManagerRegistry($managerRegistryMock);
        $repository->findBy(...$arguments);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindAllMethodCallsExpectedEntityManagerMethod(): void
    {
        [$managerRegistryMock, $entityManagerMock, $entityRepositoryMock] = $this->getMocks();

        $entityRepositoryMock
            ->expects(static::once())
            ->method('findAll')
            ->willReturn([]);

        $entityManagerMock
            ->expects(static::once())
            ->method('getRepository')
            ->with(ApiKeyEntity::class)
            ->willReturn($entityRepositoryMock);

        $managerRegistryMock
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManagerMock);

        $repository = $this->getRepositoryWithCustomManagerRegistry($managerRegistryMock);
        $repository->findAll();
    }

    /**
     * @return Generator<array{0: string, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatAddLeftJoinWorksAsExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity',
            new StringableArrayObject([]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity LEFT JOIN entity.someProperty someAlias',
            new StringableArrayObject(['entity.someProperty', 'someAlias']),
        ];

        // @codingStandardsIgnoreStart
        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity LEFT JOIN entity.someProperty someAlias WITH someAlias.someAnotherProperty = 1',
            new StringableArrayObject(['entity.someProperty', 'someAlias', Expr\Join::WITH, 'someAlias.someAnotherProperty = 1']),
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return Generator<array{0: string, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatAddInnerJoinWorksAsExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity',
            new StringableArrayObject([]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity INNER JOIN entity.someProperty someAlias',
            new StringableArrayObject(['entity.someProperty', 'someAlias']),
        ];

        // @codingStandardsIgnoreStart
        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity INNER JOIN entity.someProperty someAlias WITH someAlias.someAnotherProperty = 1',
            new StringableArrayObject(['entity.someProperty', 'someAlias', Expr\Join::WITH, 'someAlias.someAnotherProperty = 1']),
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&AbstractManagerRegistry,
     *      1: \PHPUnit\Framework\MockObject\MockObject&EntityManager,
     *      2: \PHPUnit\Framework\MockObject\MockObject&EntityRepository<ApiKeyRepository>,
     *  }
     */
    private function getMocks(): array
    {
        $managerRegistryMock = $this->getMockBuilder(AbstractManagerRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getManagerForClass', 'getService', 'resetService', 'getAliasNamespace'])
            ->getMock();

        return [
            $managerRegistryMock,
            $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock(),
        ];
    }

    private function getRepositoryWithCustomManagerRegistry(ManagerRegistry $managerRegistry): ApiKeyRepository
    {
        return new ApiKeyRepository($managerRegistry);
    }
}
