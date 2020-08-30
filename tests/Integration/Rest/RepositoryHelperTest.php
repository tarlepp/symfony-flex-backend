<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/RepositoryHelperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest;

use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\RepositoryHelper;
use Doctrine\Orm\Query\Parameter;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RepositoryHelperTest
 *
 * @package App\Tests\Integration\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RepositoryHelperTest extends KernelTestCase
{
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$container->get(UserResource::class)->getRepository();

        RepositoryHelper::resetParameterCount();
    }

    /**
     * @dataProvider dataProviderTestThatProcessCriteriaWorksAsExpected
     *
     * @testdox Test that `processCriteria` method returns `$expected`.
     */
    public function testThatProcessCriteriaWorksAsExpected(string $expected, array $input): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($qb, $input);

        $message = 'processCriteria did not return expected DQL.';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    public function testThatProcessCriteriaWorksWithEmptyCriteria(): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($qb, []);

        $expected = 'SELECT entity FROM App\\Entity\\User entity';
        $message = 'processCriteria method changed DQL when it should not - weird';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    public function testThatProcessSearchTermsWorksLikeExpectedWithoutSearchColumns(): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processSearchTerms($qb, [], ['and' => ['foo', 'bar']]);

        $message = 'processSearchTerms did not return expected DQL.';

        $expected = 'SELECT entity FROM App\\Entity\\User entity';
        $actual = $qb->getDQL();

        static::assertSame($expected, $actual, $message);
    }

    /**
     * @dataProvider dataProviderTestThatProcessSearchTermsWorksLikeExpected
     *
     * @testdox Test that `processSearchTerms` method returns `$expected`.
     */
    public function testThatProcessSearchTermsWorksLikeExpectedWithSearchColumns(string $expected, array $terms): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processSearchTerms($qb, $this->repository->getSearchColumns(), $terms);

        $message = 'processSearchTerms did not return expected DQL.';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @dataProvider dataProviderTestThatProcessOrderByWorksLikeExpected
     *
     * @testdox Test that `processOrderBy` method returns `$expected`.
     */
    public function testThatProcessOrderByWorksLikeExpected(string $expected, array $input): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processOrderBy($qb, $input);

        $message = 'processOrderBy did not return expected DQL.';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    public function testThatGetExpressionDoesNotModifyExpressionWithEmptyCriteria(): void
    {
        $queryBuilder = $this->repository->createQueryBuilder('entity');
        $expression = $queryBuilder->expr()->andX();

        $output = RepositoryHelper::getExpression($queryBuilder, $expression, []);

        $message = 'getExpression method did modify expression with no criteria - this should not happen';

        static::assertSame($expression, $output, $message);
    }

    /**
     * @dataProvider dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithSimpleCriteria
     *
     * @testdox Test that `getExpression` method returns `$expectedDQL` with simple criteria.
     */
    public function testThatGetExpressionCreatesExpectedDqlAndParametersWithSimpleCriteria(
        array $criteria,
        string $expectedDQL,
        array $expectedParameters
    ): void {
        $queryBuilder = $this->repository->createQueryBuilder('u');
        $expression = $queryBuilder->expr()->andX();

        $queryBuilder->andWhere(RepositoryHelper::getExpression($queryBuilder, $expression, [$criteria]));

        static::assertSame($expectedDQL, $queryBuilder->getQuery()->getDQL());

        /** @var Parameter $parameter */
        foreach ($queryBuilder->getParameters()->toArray() as $key => $parameter) {
            static::assertSame($expectedParameters[$key]['name'], $parameter->getName());
            static::assertSame($expectedParameters[$key]['value'], $parameter->getValue());
        }
    }

    /**
     * @dataProvider dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithComplexCriteria
     *
     * @testdox Test that `getExpression` method returns `$expectedDQL` with complex criteria.
     */
    public function testThatGetExpressionCreatesExpectedDqlAndParametersWithComplexCriteria(
        array $criteria,
        string $expectedDQL,
        array $expectedParameters
    ): void {
        $queryBuilder = $this->repository->createQueryBuilder('u');
        $expression = $queryBuilder->expr()->andX();

        $queryBuilder->andWhere(RepositoryHelper::getExpression($queryBuilder, $expression, $criteria));

        static::assertSame($expectedDQL, $queryBuilder->getQuery()->getDQL());

        /** @var Parameter $parameter */
        foreach ($queryBuilder->getParameters()->toArray() as $key => $parameter) {
            static::assertSame($expectedParameters[$key]['name'], $parameter->getName());
            static::assertSame($expectedParameters[$key]['value'], $parameter->getValue());
        }
    }

    public function dataProviderTestThatProcessCriteriaWorksAsExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1',
            ['foo' => 'bar'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE foo.bar = ?1',
            ['foo.bar' => 'foobar'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1 AND entity.bar = ?2',
            [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1',
            [
                'and' => [
                    ['bar', 'eq', 'foo'],
                ],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1',
            [
                'or' => [
                    ['bar', 'eq', 'foo'],
                ],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1 AND foo = ?2',
            [
                'and' => [
                    ['bar', 'eq', 'foo'],
                    ['foo', 'eq', 'bar'],
                ],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1 OR foo = ?2',
            [
                'or' => [
                    ['bar', 'eq', 'foo'],
                    ['foo', 'eq', 'bar'],
                ],
            ],
        ];
    }

    public function dataProviderTestThatProcessSearchTermsWorksLikeExpected(): Generator
    {
        // @codingStandardsIgnoreStart
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 AND entity.firstName LIKE ?2 AND entity.lastName LIKE ?3 AND entity.email LIKE ?4',
            [
                'and' => ['foo'],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 OR entity.firstName LIKE ?2 OR entity.lastName LIKE ?3 OR entity.email LIKE ?4',
            [
                'or' => ['foo'],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 AND entity.firstName LIKE ?2 AND entity.lastName LIKE ?3 AND entity.email LIKE ?4 AND entity.username LIKE ?5 AND entity.firstName LIKE ?6 AND entity.lastName LIKE ?7 AND entity.email LIKE ?8',
            [
                'and' => ['foo', 'bar'],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 OR entity.firstName LIKE ?2 OR entity.lastName LIKE ?3 OR entity.email LIKE ?4 OR entity.username LIKE ?5 OR entity.firstName LIKE ?6 OR entity.lastName LIKE ?7 OR entity.email LIKE ?8',
            [
                'or' => ['foo', 'bar'],
            ],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE (entity.username LIKE ?1 AND entity.firstName LIKE ?2 AND entity.lastName LIKE ?3 AND entity.email LIKE ?4) AND (entity.username LIKE ?5 OR entity.firstName LIKE ?6 OR entity.lastName LIKE ?7 OR entity.email LIKE ?8)',
            [
                'and' => ['foo'],
                'or' => ['bar'],
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function dataProviderTestThatProcessOrderByWorksLikeExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo asc',
            ['foo' => 'asc'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo DESC',
            ['foo' => 'DESC'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo asc',
            ['entity.foo' => 'asc'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY .foo asdf',
            ['.foo' => 'asdf'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY foo.bar asc',
            ['foo.bar' => 'asc'],
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo asc, foo.bar desc',
            [
                'foo' => 'asc',
                'foo.bar' => 'desc',
            ],
        ];
    }

    public function dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithSimpleCriteria(): Generator
    {
        yield [
            ['u.id', 'eq', 123],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id = ?1',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'neq', 123],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id <> ?1',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'lt', 123],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id < ?1',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'lte', 123],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id <= ?1',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'gt', 123],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id > ?1',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'gte', 123],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id >= ?1',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'in', [1, 2]],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id IN(1, 2)',
            [
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ],
        ];

        yield [
            ['u.id', 'notIn', [1, 2]],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id NOT IN(1, 2)',
            [
                [
                    'name' => '1',
                    'value' => 1,
                ],
                [
                    'name' => '2',
                    'value' => 2,
                ],
            ],
        ];

        yield [
            ['u.id', 'isNull', null],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id IS NULL',
            [],
        ];

        yield [
            ['u.id', 'isNotNull', null],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id IS NOT NULL',
            [],
        ];

        yield [
            ['u.id', 'like', 'abc'],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id LIKE ?1',
            [
                [
                    'name' => '1',
                    'value' => 'abc',
                ],
            ],
        ];

        yield [
            ['u.id', 'notLike', 'abc'],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id NOT LIKE ?1',
            [
                [
                    'name' => '1',
                    'value' => 'abc',
                ],
            ],
        ];

        yield [
            ['u.id', 'between', [1, 6]],
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id BETWEEN ?1 AND ?2',
            [
                [
                    'name' => '1',
                    'value' => 1,
                ],
                [
                    'name' => '2',
                    'value' => 6,
                ],
            ],
        ];
    }

    public function dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithComplexCriteria(): Generator
    {
        // @codingStandardsIgnoreStart
        yield [
            [
                'and' => [
                    ['u.firstName', 'eq', 'foo bar'],
                    ['u.firstName', 'neq', 'bar'],
                ],
                'or' => [
                    ['u.firstName', 'eq', 'bar foo'],
                    ['u.lastName', 'neq', 'foo'],
                ],
            ],
            /* @lang text */
            <<<'DQL'
SELECT u FROM App\Entity\User u WHERE (u.firstName = ?1 AND u.firstName <> ?2) AND (u.firstName = ?3 OR u.lastName <> ?4)
DQL
            ,
            [
                [
                    'name' => '1',
                    'value' => 'foo bar',
                ],
                [
                    'name' => '2',
                    'value' => 'bar',
                ],
                [
                    'name' => '3',
                    'value' => 'bar foo',
                ],
                [
                    'name' => '4',
                    'value' => 'foo',
                ],
            ],
        ];

        yield [
            [
                'or' => [
                    ['u.field1', 'like', '%field1Value%'],
                    ['u.field2', 'like', '%field2Value%'],
                ],
                'and' => [
                    ['u.field3', 'eq', 3],
                    ['u.field4', 'eq', 'four'],
                ],
                ['u.field5', 'neq', 5],
            ],
            /* @lang text */
            <<<'DQL'
SELECT u FROM App\Entity\User u WHERE (u.field1 LIKE ?1 OR u.field2 LIKE ?2) AND (u.field3 = ?3 AND u.field4 = ?4) AND u.field5 <> ?5
DQL
            ,
            [
                [
                    'name' => '1',
                    'value' => '%field1Value%',
                ],
                [
                    'name' => '2',
                    'value' => '%field2Value%',
                ],
                [
                    'name' => '3',
                    'value' => 3,
                ],
                [
                    'name' => '4',
                    'value' => 'four',
                ],
                [
                    'name' => '5',
                    'value' => 5,
                ],
            ],
        ];
        // @codingStandardsIgnoreEnd
    }
}
