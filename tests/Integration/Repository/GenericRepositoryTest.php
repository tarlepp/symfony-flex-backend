<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/GenericRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\EntityInterface;
use App\Entity\User as UserEntity;
use App\Repository\BaseRepositoryInterface;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class GenericRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericRepositoryTest extends KernelTestCase
{
    private $entityClass = UserEntity::class;
    private $repositoryClass = UserRepository::class;
    private $resourceClass = UserResource::class;

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testThatGetReferenceReturnsExpected(): void
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass();

        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        static::assertInstanceOf(Proxy::class, $repository->getReference($entity->getId()));

        unset($repository, $entity);
    }

    /**
     * @dataProvider dataProviderTestThatAddLeftJoinWorksAsExpected
     *
     * @param string $expected
     * @param array  $parameters
     */
    public function testThatAddLeftJoinWorksAsExpected(string $expected, array $parameters): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository->addLeftJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);

        unset($repository, $queryBuilder);
    }

    /**
     * @dataProvider dataProviderTestThatAddInnerJoinWorksAsExpected
     *
     * @param string $expected
     * @param array  $parameters
     */
    public function testThatAddInnerJoinWorksAsExpected(string $expected, array $parameters): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository->addInnerJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);

        unset($repository, $queryBuilder);
    }

    /**
     * @dataProvider dataProviderTestThatAddLeftJoinWorksAsExpected
     *
     * @param string $expected
     * @param array  $parameters
     */
    public function testThatAddLeftJoinAddsJoinJustOnce(string $expected, array $parameters): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository->addLeftJoin($parameters);
        $repository->addLeftJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);

        unset($repository, $queryBuilder);
    }

    /**
     * @dataProvider dataProviderTestThatAddInnerJoinWorksAsExpected
     *
     * @param string $expected
     * @param array  $parameters
     */
    public function testThatAddInnerJoinAddsJoinJustOnce(string $expected, array $parameters): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository->addInnerJoin($parameters);
        $repository->addInnerJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);

        unset($repository, $queryBuilder);
    }

    public function testThatAddCallbackWorks(): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder) {
            static::assertSame($queryBuilder, $qb);
            static::assertSame(1, $foo);
            static::assertSame('string', $bar);
        };

        $repository->addCallback($callable, [1, 'string']);
        $repository->processQueryBuilder($queryBuilder);

        unset($repository, $queryBuilder);
    }

    public function testThatAddCallbackCallsCallbackJustOnce(): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = self::$container->get($this->resourceClass)->getRepository();

        $count = 0;

        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder, &$count) {
            static::assertSame($queryBuilder, $qb);
            static::assertSame(1, $foo);
            static::assertSame('string', $bar);

            $count++;
        };

        // Attach same callback twice
        $repository->addCallback($callable, [1, 'string']);
        $repository->addCallback($callable, [1, 'string']);

        // Process query builder
        $repository->processQueryBuilder($queryBuilder);

        static::assertSame(1, $count);

        unset($repository, $queryBuilder);
    }

    public function testThatFindMethodCallsExpectedEntityManagerMethod(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractManagerRegistry $managerObject
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager           $entityManager
         */
        $managerObject = $this->getMockBuilder(AbstractManagerRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass', 'getService', 'resetService', 'getAliasNamespace'])
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
            ->expects(static::once())
            ->method('find')
            ->with(
                $this->entityClass,
                'id',
                null,
                null
            );

        $managerObject
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        /**
         * @var BaseRepositoryInterface $repository
         */
        $repository = new $this->repositoryClass($managerObject);
        $repository->find(...$arguments);

        unset($repository, $managerObject, $entityManager);
    }

    public function testThatFindOneByMethodCallsExpectedEntityManagerMethod(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractManagerRegistry $managerObject
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager           $entityManager
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository        $repositoryMock
         */
        $managerObject = $this->getMockBuilder(AbstractManagerRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass', 'getService', 'resetService', 'getAliasNamespace'])
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $arguments = [
            ['some criteria'],
            ['some order by']
        ];

        $repositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(...$arguments);

        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with($this->entityClass)
            ->willReturn($repositoryMock);

        $managerObject
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        /**
         * @var BaseRepositoryInterface $repository
         */
        $repository = new $this->repositoryClass($managerObject);
        $repository->findOneBy(...$arguments);

        unset($repository, $managerObject, $entityManager, $repositoryMock);
    }

    public function testThatFindByMethodCallsExpectedEntityManagerMethod(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractManagerRegistry $managerObject
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager           $entityManager
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository        $repositoryMock
         */
        $managerObject = $this->getMockBuilder(AbstractManagerRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass', 'getService', 'resetService', 'getAliasNamespace'])
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
            10,
            20,
        ];

        $repositoryMock
            ->expects(static::once())
            ->method('findBy')
            ->with(...$arguments)
            ->willReturn([]);

        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with($this->entityClass)
            ->willReturn($repositoryMock);

        $managerObject
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        /**
         * @var BaseRepositoryInterface $repository
         */
        $repository = new $this->repositoryClass($managerObject);
        $repository->findBy(...$arguments);

        unset($repository, $managerObject, $entityManager, $repositoryMock);
    }

    public function testThatFindAllMethodCallsExpectedEntityManagerMethod(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractManagerRegistry $managerObject
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager           $entityManager
         * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository        $repositoryMock
         */
        $managerObject = $this->getMockBuilder(AbstractManagerRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass', 'getService', 'resetService', 'getAliasNamespace'])
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock
            ->expects(static::once())
            ->method('findAll')
            ->willReturn([]);

        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with($this->entityClass)
            ->willReturn($repositoryMock);

        $managerObject
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        /**
         * @var BaseRepositoryInterface $repository
         */
        $repository = new $this->repositoryClass($managerObject);
        $repository->findAll();

        unset($repository, $managerObject, $entityManager, $repositoryMock);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAddLeftJoinWorksAsExpected(): array
    {
        // @codingStandardsIgnoreStart
        return [
            [
                /** @lang text */
                'SELECT entity FROM App\\Entity\\User entity',
                [],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\\Entity\\User entity LEFT JOIN entity.someProperty someAlias',
                ['entity.someProperty', 'someAlias'],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\\Entity\\User entity LEFT JOIN entity.someProperty someAlias WITH someAlias.someAnotherProperty = 1',
                ['entity.someProperty', 'someAlias', Expr\Join::WITH, 'someAlias.someAnotherProperty = 1'],
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAddInnerJoinWorksAsExpected(): array
    {
        // @codingStandardsIgnoreStart
        return [
            [
                /** @lang text */
                'SELECT entity FROM App\\Entity\\User entity',
                [],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\\Entity\\User entity INNER JOIN entity.someProperty someAlias',
                ['entity.someProperty', 'someAlias'],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\\Entity\\User entity INNER JOIN entity.someProperty someAlias WITH someAlias.someAnotherProperty = 1',
                ['entity.someProperty', 'someAlias', Expr\Join::WITH, 'someAlias.someAnotherProperty = 1'],
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        static::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        gc_collect_cycles();
    }
}
