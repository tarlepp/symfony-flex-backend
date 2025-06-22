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
use App\Repository\RoleRepository;
use App\Resource\ApiKeyResource;
use App\Tests\Utils\StringableArrayObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use UnexpectedValueException;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class GenericRepositoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatGetReferenceReturnsExpected(): void
    {
        $entity = new ApiKeyEntity();
        $resource = self::getContainer()->get(ApiKeyResource::class);

        self::assertInstanceOf(ApiKeyEntity::class, $resource->getRepository()->getReference($entity->getId()));
    }

    /**
     * @throws Throwable
     */
    public function testThatGetReferenceReturnsExpectedWithNonUuidInput(): void
    {
        $repository = self::getContainer()->get(RoleRepository::class);

        self::assertInstanceOf(Role::class, $repository->getReference('some-role'));
    }

    /**
     * @throws Throwable
     */
    public function testThatGetAssociationsReturnsExpected(): void
    {
        $resource = self::getContainer()->get(ApiKeyResource::class);

        self::assertSame(
            ['userGroups', 'logsRequest', 'createdBy', 'updatedBy'],
            array_keys($resource->getRepository()->getAssociations())
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatGetClassMetaDataReturnsExpected(): void
    {
        $resource = self::getContainer()->get(ApiKeyResource::class);

        self::assertInstanceOf(ClassMetadata::class, $resource->getRepository()->getClassMetaData());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityManagerThrowsAnExceptionIfManagerIsNotValid(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot get entity manager for entity \'App\Entity\ApiKey\'');

        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $managerObject
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $repository = new ApiKeyRepository($managerObject);
        $repository->getEntityManager();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityManagerDoNotResetExistingManagerIfItIsOpen(): void
    {
        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);

        $managerObject
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $repository = new ApiKeyRepository($managerObject);

        self::assertSame($entityManager, $repository->getEntityManager());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityManagerResetManagerIfItIsNotOpen(): void
    {
        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
            ->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);

        $secondEntityManager = clone $entityManager;

        $managerObject
            ->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($entityManager, $secondEntityManager);

        $managerObject
            ->expects($this->once())
            ->method('resetManager');

        $repository = new ApiKeyRepository($managerObject);

        $actualEntityManager = $repository->getEntityManager();

        self::assertNotSame($entityManager, $actualEntityManager);
        self::assertSame($secondEntityManager, $actualEntityManager);
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatAddLeftJoinWorksAsExpected')]
    #[TestDox("Test that add left join works as expected, using \$parameters and expecting '\$expected'")]
    public function testThatAddLeftJoinWorksAsExpected(string $expected, StringableArrayObject $parameters): void
    {
        $apiKeyResource = self::getContainer()->get(ApiKeyResource::class);
        $repository = $apiKeyResource->getRepository();
        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository
            ->addLeftJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        self::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatAddInnerJoinWorksAsExpected')]
    #[TestDox("Test that add inner join works as expected, using \$parameters and expecting '\$expected'")]
    public function testThatAddInnerJoinWorksAsExpected(string $expected, StringableArrayObject $parameters): void
    {
        $apiKeyResource = self::getContainer()->get(ApiKeyResource::class);
        $repository = $apiKeyResource->getRepository();
        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository
            ->addInnerJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        self::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatAddLeftJoinWorksAsExpected')]
    #[TestDox("Test that add left join adds same join just once, using \$parameters and expecting '\$expected'")]
    public function testThatAddLeftJoinAddsJoinJustOnce(string $expected, StringableArrayObject $parameters): void
    {
        $apiKeyResource = self::getContainer()->get(ApiKeyResource::class);
        $repository = $apiKeyResource->getRepository();
        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository
            ->addLeftJoin($parameters->getArrayCopy())
            ->addLeftJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        self::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatAddInnerJoinWorksAsExpected')]
    #[TestDox("Test that add inner join adds same join just once, using \$parameters and expecting '\$expected'")]
    public function testThatAddInnerJoinAddsJoinJustOnce(string $expected, StringableArrayObject $parameters): void
    {
        $apiKeyResource = self::getContainer()->get(ApiKeyResource::class);
        $repository = $apiKeyResource->getRepository();
        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository
            ->addInnerJoin($parameters->getArrayCopy())
            ->addInnerJoin($parameters->getArrayCopy())
            ->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        self::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    /**
     * @throws Throwable
     */
    public function testThatAddCallbackWorks(): void
    {
        $apiKeyResource = self::getContainer()->get(ApiKeyResource::class);
        $repository = $apiKeyResource->getRepository();
        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = static function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder): void {
            self::assertSame($queryBuilder, $qb);
            self::assertSame(1, $foo);
            self::assertSame('string', $bar);
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
        $apiKeyResource = self::getContainer()->get(ApiKeyResource::class);
        $repository = $apiKeyResource->getRepository();

        $count = 0;

        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = static function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder, &$count): void {
            self::assertSame($queryBuilder, $qb);
            self::assertSame(1, $foo);
            self::assertSame('string', $bar);

            $count++;
        };

        // Attach same callback twice
        $repository
            ->addCallback($callable, [1, 'string'])
            ->addCallback($callable, [1, 'string']);

        // Process query builder
        $repository->processQueryBuilder($queryBuilder);

        self::assertSame(1, $count);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindMethodCallsExpectedEntityManagerMethod(): void
    {
        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $arguments = [
            'id',
            null,
            null,
        ];

        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(
                ApiKeyEntity::class,
                'id',
                null,
                null
            );

        $managerObject
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $repository = new ApiKeyRepository($managerObject);
        $repository->find(...$arguments);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneByMethodCallsExpectedEntityManagerMethod(): void
    {
        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $arguments = [
            ['some criteria'],
            ['some order by'],
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(...$arguments);

        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(ApiKeyEntity::class)
            ->willReturn($repositoryMock);

        $managerObject
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $repository = new ApiKeyRepository($managerObject);
        $repository->findOneBy(...$arguments);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindByMethodCallsExpectedEntityManagerMethod(): void
    {
        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $arguments = [
            [
                'foo' => 'some criteria',
            ],
            [
                'bar' => 'some order by',
            ],
            10,
            20,
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('findBy')
            ->with(...$arguments)
            ->willReturn([]);

        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(ApiKeyEntity::class)
            ->willReturn($repositoryMock);

        $managerObject
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $repository = new ApiKeyRepository($managerObject);
        $repository->findBy(...$arguments);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindAllMethodCallsExpectedEntityManagerMethod(): void
    {
        $managerObject = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(ApiKeyEntity::class)
            ->willReturn($repositoryMock);

        $managerObject
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $repository = new ApiKeyRepository($managerObject);
        $repository->findAll();
    }

    /**
     * @psalm-return Generator<array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatAddLeftJoinWorksAsExpected(): Generator
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
     * @psalm-return Generator<array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatAddInnerJoinWorksAsExpected(): Generator
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

        yield [
            /* @lang text */
            'SELECT entity FROM App\\Entity\\ApiKey entity INNER JOIN entity.someProperty someAlias WITH ' .
            'someAlias.someAnotherProperty = 1',
            new StringableArrayObject([
                'entity.someProperty',
                'someAlias',
                Expr\Join::WITH,
                'someAlias.someAnotherProperty = 1',
            ]),
        ];
    }
}
