<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Rest/RepositoryHelperTest.php
 */

namespace App\Tests\Integration\Rest;

use App\Repository\UserRepository;
use App\Rest\RepositoryHelper;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SortDirection;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function hex2bin;

final class RepositoryHelperTest extends KernelTestCase
{
    /**
     * @phpstan-param StringableArrayObject<mixed> $input
     * @psalm-param StringableArrayObject $input
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatProcessCriteriaWorksAsExpected')]
    #[TestDox('Test that after `processCriteria` method call DQL is `$expected` when using `$input` as input')]
    public function testThatProcessCriteriaWorksAsExpected(string $expected, StringableArrayObject $input): void
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($qb, $input->getArrayCopy());

        $message = 'processCriteria did not return expected DQL.';

        self::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that `processCriteria` method call doesn't change DQL with empty `criteria` parameter")]
    public function testThatProcessCriteriaWorksWithEmptyCriteria(): void
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($qb, []);

        $expected = <<<'DQL'
SELECT entity FROM App\Entity\User entity
DQL;
        $message = 'processCriteria method changed DQL when it should not - weird';

        self::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that `processSearchTerms` method call doesn't change DQL with empty `columns` parameter")]
    public function testThatProcessSearchTermsWorksLikeExpectedWithoutSearchColumns(): void
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('entity');

        RepositoryHelper::processSearchTerms(
            $qb,
            [],
            [
                'and' => ['foo', 'bar'],
            ],
        );

        $message = 'processSearchTerms did not return expected DQL.';

        $expected = <<<'DQL'
SELECT entity FROM App\Entity\User entity
DQL;
        $actual = $qb->getDQL();

        self::assertSame($expected, $actual, $message);
    }

    /**
     * @phpstan-param StringableArrayObject<array> $terms
     * @psalm-param StringableArrayObject $terms
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatProcessSearchTermsWorksLikeExpected')]
    #[TestDox('Test that after `processSearchTerms` method call DQL is `$expected` when using `$terms` as terms')]
    public function testThatProcessSearchTermsWorksLikeExpectedWithSearchColumns(
        string $expected,
        StringableArrayObject $terms,
    ): void {
        $qb = $this->getRepository()
            ->createQueryBuilder('entity');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        RepositoryHelper::processSearchTerms($qb, $this->getRepository()->getSearchColumns(), $terms->getArrayCopy());

        $message = 'processSearchTerms did not return expected DQL.';

        self::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `processOrderBy` method throws an exception for an invalid sort direction')]
    public function testThatProcessOrderByThrowsExceptionForInvalidSortDirection(): void
    {
        $qb = $this->getRepository()->createQueryBuilder('entity');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Invalid sort direction "asdf".$/');

        RepositoryHelper::processOrderBy($qb, [
            '.foo' => 'asdf',
        ]);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `processOrderBy` accepts a `SortDirection` enum')]
    public function testThatProcessOrderByAcceptsSortDirectionEnum(): void
    {
        $qb = $this->getRepository()->createQueryBuilder('entity');

        RepositoryHelper::processOrderBy($qb, [
            'foo' => SortDirection::Ascending,
        ]);

        self::assertSame(
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo ASC',
            $qb->getDQL(),
        );
    }

    /**
     * @phpstan-param StringableArrayObject<array> $input
     * @psalm-param StringableArrayObject $input
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatProcessOrderByWorksLikeExpected')]
    #[TestDox('Test that after `processOrderBy` method call DQL is `$expected` when using `$input` as input')]
    public function testThatProcessOrderByWorksLikeExpected(string $expected, StringableArrayObject $input): void
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('entity');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        RepositoryHelper::processOrderBy($qb, $input->getArrayCopy());

        $message = 'processOrderBy did not return expected DQL.';

        self::assertSame($expected, $qb->getDQL(), $message);
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that `getExpression` method doesn't modify expression with empty `criteria` parameter")]
    public function testThatGetExpressionDoesNotModifyExpressionWithEmptyCriteria(): void
    {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('entity');
        $expression = $queryBuilder->expr()
            ->andX();

        $output = RepositoryHelper::getExpression($queryBuilder, $expression, []);

        $message = 'getExpression method did modify expression with no criteria - this should not happen';

        self::assertSame($expression, $output, $message);
    }

    /**
     * @throws Throwable
     */
    public function testThatEmptyGroupsProduceNoExpressionOrBindings(): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');
        $expression = $queryBuilder->expr()->andX();

        $output = RepositoryHelper::getExpression($queryBuilder, $expression, [
            [
                'and' => [],
                'or' => [],
            ],
        ]);

        self::assertSame($expression, $output);
        self::assertSame('', (string)$output);
        self::assertCount(0, $queryBuilder->getParameters());
        self::assertSame(0, RepositoryHelper::$parameterCount);
    }

    /**
     * @param array<array-key, mixed> $criteria Malformed comparisons or groups supplied to the public array boundary.
     *
     * @throws Throwable
     */
    #[DataProvider('invalidExpressionCriteria')]
    public function testThatGetExpressionRejectsMalformedCriteria(array $criteria, string $message): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs($message);

        RepositoryHelper::getExpression($queryBuilder, $queryBuilder->expr()->andX(), $criteria);
    }

    /**
     * @throws Throwable
     */
    public function testThatProcessCriteriaRejectsScalarGroups(): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Criteria AND/OR groups must be arrays.');

        RepositoryHelper::processCriteria($queryBuilder, [
            'and' => 'invalid',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testThatProcessCriteriaPreservesScalarCoercionAndListValues(): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');

        RepositoryHelper::processCriteria($queryBuilder, [
            'foo' => 123,
            'bar' => false,
            'baz' => [1, true, false, 2.5, 'text'],
        ]);

        self::assertSame(
            'SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1 AND entity.bar = ?2'
                . " AND entity.baz IN(1, 1, '', 2, 'text')",
            $queryBuilder->getDQL(),
        );
        self::assertSame('123', $queryBuilder->getParameter(1)?->getValue());
        self::assertSame('', $queryBuilder->getParameter(2)?->getValue());
    }

    /**
     * @throws Throwable
     */
    public function testThatProcessCriteriaAcceptsStringableValues(): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');
        $value = new StringableArrayObject([1]);

        RepositoryHelper::processCriteria($queryBuilder, [
            'foo' => $value,
            'bar' => [$value],
        ]);

        self::assertSame(
            "SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1 AND entity.bar IN('[1]')",
            $queryBuilder->getDQL(),
        );
        self::assertSame('[1]', $queryBuilder->getParameter(1)?->getValue());
    }

    /**
     * @throws Throwable
     */
    public function testThatProcessCriteriaPreservesUuidListConversion(): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');
        $uuid = '12345678-1234-1234-9234-123456789abc';

        RepositoryHelper::processCriteria($queryBuilder, [
            'id' => [$uuid],
        ]);

        $bytes = hex2bin('12341234123456789234123456789abc');
        self::assertNotFalse($bytes);
        self::assertSame(
            'SELECT entity FROM App\Entity\User entity WHERE entity.id IN(\'' . $bytes . '\')',
            $queryBuilder->getDQL(),
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    /**
     * @throws Throwable
     */
    public function testThatProcessCriteriaPreservesMixedUuidListFallback(): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('entity');
        $uuid = '12345678-1234-1234-9234-123456789abc';

        RepositoryHelper::processCriteria($queryBuilder, [
            'id' => [$uuid, 'text'],
        ]);

        self::assertSame(
            "SELECT entity FROM App\Entity\User entity WHERE entity.id IN('" . $uuid . "', 'text')",
            $queryBuilder->getDQL(),
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    /**
     * @phpstan-param StringableArrayObject<array> $criteria
     * @phpstan-param StringableArrayObject<array> $params
     * @psalm-param StringableArrayObject $criteria
     * @psalm-param StringableArrayObject $params
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithCriteria')]
    #[TestDox('Test that after `getExpression` call DQL is `$dql` and parameters are `$params` when using `$criteria`')]
    public function testThatGetExpressionCreatesExpectedDqlAndParametersWithSimpleCriteria(
        StringableArrayObject $criteria,
        string $dql,
        StringableArrayObject $params,
    ): void {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('u');
        $expression = $queryBuilder->expr()
            ->andX();

        $queryBuilder->andWhere(
            RepositoryHelper::getExpression($queryBuilder, $expression, [$criteria->getArrayCopy()]),
        );

        self::assertSame($dql, $queryBuilder->getQuery()->getDQL());
        self::assertCount($params->count(), $queryBuilder->getParameters());

        foreach ($queryBuilder->getParameters()->toArray() as $key => $parameter) {
            /** @var array{name: string, value: mixed} $param */
            $param = $params[$key];
            self::assertSame($param['name'], $parameter->getName());
            self::assertSame($param['value'], $parameter->getValue());
        }
    }

    /**
     * @psalm-return Generator<int, array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatProcessCriteriaWorksAsExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE entity.foo = ?1',
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity WHERE foo.bar = ?1',
            new StringableArrayObject([
                'foo.bar' => 'foobar',
            ]),
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

    /**
     * @psalm-return Generator<int, array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatProcessSearchTermsWorksLikeExpected(): Generator
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

    /**
     * @psalm-return Generator<int, array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatProcessOrderByWorksLikeExpected(): Generator
    {
        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo ASC',
            new StringableArrayObject([
                'foo' => 'asc',
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo DESC',
            new StringableArrayObject([
                'foo' => 'DESC',
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo ASC',
            new StringableArrayObject([
                'entity.foo' => 'asc',
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY foo.bar ASC',
            new StringableArrayObject([
                'foo.bar' => 'asc',
            ]),
        ];

        yield [
            /* @lang text */
            'SELECT entity FROM App\Entity\User entity ORDER BY entity.foo ASC, foo.bar DESC',
            new StringableArrayObject([
                'foo' => 'asc',
                'foo.bar' => 'desc',
            ]),
        ];
    }

    /**
     * @return Generator<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidExpressionCriteria(): Generator
    {
        $tupleMessage = 'A criteria comparison must contain exactly a field, operator, and value.';
        $listMessage = 'Criteria list and range operands must be scalars or stringable objects.';
        $rangeMessage = 'The BETWEEN operator requires exactly two indexed bounds.';

        yield 'scalar group' => [[
            'or' => 'invalid',
        ], 'Criteria groups must contain comparison arrays or nested groups.'];

        yield 'scalar child' => [[
            'and' => [1],
        ], 'Criteria groups must contain comparison arrays or nested groups.'];

        yield 'missing operand' => [[['entity.id', 'eq']], $tupleMessage];

        yield 'extra operand' => [[['entity.id', 'eq', 1, 2]], $tupleMessage];

        yield 'non-string field' => [[[1, 'eq', 1]], 'Criteria fields and operators must be strings.'];

        yield 'non-string operator' => [[['entity.id', 1, 1]], 'Criteria fields and operators must be strings.'];

        yield 'unknown operator' => [[['entity.id', 'unknown', 1]], 'Unknown criteria expression operator.'];

        yield 'object operand' => [
            [['entity.id', 'eq', new stdClass()]],
            'Criteria operands must be scalars, stringable objects, arrays, or null.',
        ];

        yield 'nested list' => [[['entity.id', 'in', [[1]]]], $listMessage];

        yield 'null list item' => [[['entity.id', 'in', [null]]], $listMessage];

        yield 'IN scalar' => [[['entity.id', 'in', 1]], 'The IN and NOT IN operators require an array operand.'];

        yield 'NOT IN scalar' => [
            [['entity.id', 'notIn', '1']],
            'The IN and NOT IN operators require an array operand.',
        ];

        yield 'BETWEEN scalar' => [[['entity.id', 'between', 1]], $rangeMessage];

        yield 'BETWEEN too short' => [[['entity.id', 'between', [1]]], $rangeMessage];

        yield 'BETWEEN too long' => [[['entity.id', 'between', [1, 2, 3]]], $rangeMessage];

        yield 'BETWEEN named bounds' => [[[
            'entity.id', 'between', [
                'min' => 1,
                'max' => 2,
            ]]], 'The BETWEEN operator requires exactly two indexed bounds.'];
    }

    /**
     * @psalm-return Generator<int, array{0: StringableArrayObject, 1: string, 2: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: string, 2: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatGetExpressionCreatesExpectedDqlAndParametersWithCriteria(): Generator
    {
        yield [
            new StringableArrayObject([
                'field' => 'u.id',
                'operator' => 'EQ',
                'value' => 123,
            ]),
            'SELECT u FROM App\Entity\User u WHERE u.id = ?1',
            new StringableArrayObject([[
                'name' => '1',
                'value' => 123,
            ]]),
        ];

        yield [
            new StringableArrayObject(['u.firstName', 'concat', 'suffix']),
            'SELECT u FROM App\Entity\User u WHERE CONCAT(u.firstName, ?1)',
            new StringableArrayObject([[
                'name' => '1',
                'value' => 'suffix',
            ]]),
        ];

        yield [
            new StringableArrayObject(['u.id', 'in', []]),
            'SELECT u FROM App\Entity\User u WHERE u.id IN()',
            new StringableArrayObject([]),
        ];

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

    /**
     * @throws Throwable
     */
    private function getRepository(): UserRepository
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);

        RepositoryHelper::resetParameterCount();

        return $userRepository;
    }
}
