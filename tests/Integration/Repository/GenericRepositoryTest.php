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
use App\Resource\UserResource;
use Doctrine\Common\Proxy\Proxy;
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
    private $resourceClass = UserResource::class;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testThatGetReferenceReturnsExpected(): void
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass();

        /** @var BaseRepositoryInterface $repository */
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        static::assertInstanceOf(Proxy::class, $repository->getReference($entity->getId()));
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
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository->addLeftJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
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
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $repository->addInnerJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
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
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository->addLeftJoin($parameters);
        $repository->addLeftJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
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
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        // Add same join twice to query
        $repository->addInnerJoin($parameters);
        $repository->addInnerJoin($parameters);
        $repository->processQueryBuilder($queryBuilder);

        $message = 'addLeftJoin method did not return expected';

        static::assertSame($expected, $queryBuilder->getDQL(), $message);
    }

    public function testThatAddCallbackWorks(): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        $queryBuilder = $repository->createQueryBuilder('entity');

        $callable = function (QueryBuilder $qb, int $foo, string $bar) use ($queryBuilder) {
            static::assertSame($queryBuilder, $qb);
            static::assertSame(1, $foo);
            static::assertSame('string', $bar);
        };

        $repository->addCallback($callable, [1, 'string']);
        $repository->processQueryBuilder($queryBuilder);
    }

    public function testThatAddCallbackCallsCallbackJustOnce(): void
    {
        /** @var BaseRepositoryInterface $repository */
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

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
}
