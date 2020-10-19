<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/RepositoryHelperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest;

use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\RepositoryHelper;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\Orm\Query\Parameter;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RepositoryHelperTest
 *
 * @package App\Tests\Integration\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RepositoryHelperTest extends KernelTestCase
{
    protected UserRepository $repository;

    /**
     * @dataProvider dataProviderTestThatProcessCriteriaWorksAsExpected
     *
     * @testdox Test that after `processCriteria` method call DQL is `$expected` when using `$input` as input
     */
    public function testThatProcessCriteriaWorksAsExpected(string $expected, StringableArrayObject $input): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($qb, $input->getArrayCopy());

        $message = 'processCriteria did not return expected DQL.';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @testdox Test that `processCriteria` method call doesn't change DQL with empty `criteria` parameter
     */
    public function testThatProcessCriteriaWorksWithEmptyCriteria(): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($qb, []);

        $expected = 'SELECT entity FROM App\\Entity\\User entity';
        $message = 'processCriteria method changed DQL when it should not - weird';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @testdox Test that `processSearchTerms` method call doesn't change DQL with empty `columns` parameter
     */
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
     * @testdox Test that after `processSearchTerms` method call DQL is `$expected` when using `$terms` as terms
     */
    public function testThatProcessSearchTermsWorksLikeExpectedWithSearchColumns(
        string $expected,
        StringableArrayObject $terms
    ): void {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processSearchTerms($qb, $this->repository->getSearchColumns(), $terms->getArrayCopy());

        $message = 'processSearchTerms did not return expected DQL.';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @dataProvider dataProviderTestThatProcessOrderByWorksLikeExpected
     *
     * @testdox Test that after `processOrderBy` method call DQL is `$expected` when using `$input` as input
     */
    public function testThatProcessOrderByWorksLikeExpected(string $expected, StringableArrayObject $input): void
    {
        $qb = $this->repository->createQueryBuilder('entity');

        RepositoryHelper::processOrderBy($qb, $input->getArrayCopy());

        $message = 'processOrderBy did not return expected DQL.';

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @testdox Test that `getExpression` method doesn't modify expression with empty `criteria` parameter
     */
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
     * @testdox Test that after `getExpression` call DQL is `$dql` and parameters are `$params` when using `$criteria`
     */
    public function testThatGetExpressionCreatesExpectedDqlAndParametersWithSimpleCriteria(
        StringableArrayObject $criteria,
        string $dql,
        StringableArrayObject $params
    ): void {
        $queryBuilder = $this->repository->createQueryBuilder('u');
        $expression = $queryBuilder->expr()->andX();

        $queryBuilder->andWhere(
            RepositoryHelper::getExpression($queryBuilder, $expression, [$criteria->getArrayCopy()])
        );

        static::assertSame($dql, $queryBuilder->getQuery()->getDQL());
        static::assertCount($params->count(), $queryBuilder->getParameters());

        /** @var Parameter $parameter */
        foreach ($queryBuilder->getParameters()->toArray() as $key => $parameter) {
            static::assertSame($params[$key]['name'], $parameter->getName());
            static::assertSame($params[$key]['value'], $parameter->getValue());
        }
    }

    public function dataProviderTestThatProcessCriteriaWorksAsExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1',
            new StringableArrayObject(['foo' => 'bar']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE foo.bar = ?1',
            new StringableArrayObject(['foo.bar' => 'foobar']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1 AND entity.bar = ?2',
            new StringableArrayObject([
                'foo' => 'bar',
                'bar' => 'foo',
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1',
            new StringableArrayObject([
                'and' => [
                    ['bar', 'eq', 'foo'],
                ],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1',
            new StringableArrayObject([
                'or' => [
                    ['bar', 'eq', 'foo'],
                ],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1 AND foo = ?2',
            new StringableArrayObject([
                'and' => [
                    ['bar', 'eq', 'foo'],
                    ['foo', 'eq', 'bar'],
                ],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE bar = ?1 OR foo = ?2',
            new StringableArrayObject([
                'or' => [
                    ['bar', 'eq', 'foo'],
                    ['foo', 'eq', 'bar'],
                ],
            ]),
        ];
    }

    public function dataProviderTestThatProcessSearchTermsWorksLikeExpected(): Generator
    {
        // @codingStandardsIgnoreStart
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 AND entity.firstName LIKE ?2 AND entity.lastName LIKE ?3 AND entity.email LIKE ?4',
            new StringableArrayObject([
                'and' => ['foo'],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 OR entity.firstName LIKE ?2 OR entity.lastName LIKE ?3 OR entity.email LIKE ?4',
            new StringableArrayObject([
                'or' => ['foo'],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 AND entity.firstName LIKE ?2 AND entity.lastName LIKE ?3 AND entity.email LIKE ?4 AND entity.username LIKE ?5 AND entity.firstName LIKE ?6 AND entity.lastName LIKE ?7 AND entity.email LIKE ?8',
            new StringableArrayObject([
                'and' => ['foo', 'bar'],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.username LIKE ?1 OR entity.firstName LIKE ?2 OR entity.lastName LIKE ?3 OR entity.email LIKE ?4 OR entity.username LIKE ?5 OR entity.firstName LIKE ?6 OR entity.lastName LIKE ?7 OR entity.email LIKE ?8',
            new StringableArrayObject([
                'or' => ['foo', 'bar'],
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE (entity.username LIKE ?1 AND entity.firstName LIKE ?2 AND entity.lastName LIKE ?3 AND entity.email LIKE ?4) AND (entity.username LIKE ?5 OR entity.firstName LIKE ?6 OR entity.lastName LIKE ?7 OR entity.email LIKE ?8)',
            new StringableArrayObject([
                'and' => ['foo'],
                'or' => ['bar'],
            ]),
        ];
        // @codingStandardsIgnoreEnd
    }

    public function dataProviderTestThatProcessOrderByWorksLikeExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo asc',
            new StringableArrayObject(['foo' => 'asc']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo DESC',
            new StringableArrayObject(['foo' => 'DESC']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo asc',
            new StringableArrayObject(['entity.foo' => 'asc']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY .foo asdf',
            new StringableArrayObject(['.foo' => 'asdf']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY foo.bar asc',
            new StringableArrayObject(['foo.bar' => 'asc']),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo asc, foo.bar desc',
            new StringableArrayObject([
                'foo' => 'asc',
                'foo.bar' => 'desc',
            ]),
        ];
    }

    public function dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithSimpleCriteria(): Generator
    {
        yield [
            new StringableArrayObject(['u.id', 'eq', 123]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id = ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'neq', 123]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id <> ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'lt', 123]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id < ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'lte', 123]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id <= ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'gt', 123]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id > ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'gte', 123]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id >= ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 123,
                ],
            ]),
        ];


        yield [
            new StringableArrayObject(['u.id', 'in', [1, 2]]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id IN(1, 2)',
            new StringableArrayObject([]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'notIn', [1, 2]]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id NOT IN(1, 2)',
            new StringableArrayObject([]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'isNull', null]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id IS NULL',
            new StringableArrayObject([]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'isNotNull', null]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id IS NOT NULL',
            new StringableArrayObject([]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'like', 'abc']),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id LIKE ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 'abc',
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'notLike', 'abc']),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id NOT LIKE ?1',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 'abc',
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'between', [1, 6]]),
            /* @lang text */
            'SELECT u FROM App\Entity\User u WHERE u.id BETWEEN ?1 AND ?2',
            new StringableArrayObject([
                [
                    'name' => '1',
                    'value' => 1,
                ],
                [
                    'name' => '2',
                    'value' => 6,
                ],
            ]),
        ];

        // @codingStandardsIgnoreStart
        yield [
            new StringableArrayObject([
                'and' => [
                    ['u.firstName', 'eq', 'foo bar'],
                    ['u.firstName', 'neq', 'bar'],
                ],
                'or' => [
                    ['u.firstName', 'eq', 'bar foo'],
                    ['u.lastName', 'neq', 'foo'],
                ],
            ]),
            /* @lang text */
            <<<'DQL'
SELECT u FROM App\Entity\User u WHERE (u.firstName = ?1 AND u.firstName <> ?2) AND (u.firstName = ?3 OR u.lastName <> ?4)
DQL
            ,
            new StringableArrayObject([
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
            ]),
        ];

        yield [
            new StringableArrayObject([
                'or' => [
                    ['u.field1', 'like', '%field1Value%'],
                    ['u.field2', 'like', '%field2Value%'],
                ],
                'and' => [
                    ['u.field3', 'eq', 3],
                    ['u.field4', 'eq', 'four'],
                ],
                ['u.field5', 'neq', 5],
            ]),
            /* @lang text */
            <<<'DQL'
SELECT u FROM App\Entity\User u WHERE (u.field1 LIKE ?1 OR u.field2 LIKE ?2) AND (u.field3 = ?3 AND u.field4 = ?4) AND u.field5 <> ?5
DQL
            ,
            new StringableArrayObject([
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
            ]),
        ];
        // @codingStandardsIgnoreEnd
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$container->get(UserResource::class)->getRepository();

        RepositoryHelper::resetParameterCount();
    }
}
