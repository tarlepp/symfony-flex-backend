<?php
declare(strict_types = 1);

/**
 * /src/Rest/RepositoryHelper.php
 */

namespace App\Rest;

use App\Rest\Criteria\Comparison;
use App\Rest\Criteria\Group;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Literal;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use SortDirection;
use Stringable;
use function array_map;
use function call_user_func_array;
use function is_array;
use function is_numeric;
use function sprintf;
use function str_contains;
use function strtolower;
use function syslog;

/**
 * @phpstan-import-type Operand from Comparison
 * @phpstan-type ExpressionParameters list<string|array<array-key, Literal>>
 */
class RepositoryHelper
{
    /**
     * Parameter count in current query, this is used to track parameters which
     * are bind to current query.
     */
    public static int $parameterCount = 0;

    /**
     * Method to reset current parameter count value
     */
    public static function resetParameterCount(): void
    {
        self::$parameterCount = 0;
    }

    /**
     * Process given criteria which is given by ?where parameter. This is given
     * as JSON string, which is converted to assoc array for this process.
     *
     * Note that this supports by default (without any extra work) just 'eq'
     * and 'in' expressions. See example array below:
     *
     *  [
     *      'u.id' => 3,
     *      'u.uid' => 'uid',
     *      'u.foo' => [1, 2, 3],
     *      'u.bar' => ['foo', 'bar'],
     *  ]
     *
     * And these you can make easily happen within REST controller and simple
     * 'where' parameter. See example below:
     *
     *  ?where={"u.id":3,"u.uid":"uid","u.foo":[1,2,3],"u.bar":["foo","bar"]}
     *
     * Also note that you can make more complex use case fairly easy, just
     * follow instructions below.
     *
     * If you're trying to make controller specified special criteria with
     * projects generic Rest controller, just add 'processCriteria(array &$criteria)'
     * method to your own controller and pre-process that criteria in there
     * the way you want it to be handled. In other words just modify that basic
     * key-value array just as you like it, main goal is to create array that
     * is compatible with 'getExpression' method in this class. For greater
     * detail just see that method comments.
     *
     * tl;dr Modify your $criteria parameter in your controller with
     * 'processCriteria(array &$criteria)' method.
     *
     * @see \App\Repository\Traits\RepositoryMethodsTrait::getQueryBuilder()
     *
     * @param array<int|string, mixed>|null $criteria Field filters or nested comparison groups;
     *                                                null applies no filters.
     *
     * @throws InvalidArgumentException
     */
    public static function processCriteria(QueryBuilder $queryBuilder, ?array $criteria = null): void
    {
        $criteria ??= [];

        if ($criteria === []) {
            return;
        }

        $expression = $queryBuilder->expr()->andX();

        self::processExpression($queryBuilder, $expression, Group::fromFilters($criteria));

        $queryBuilder->andWhere($expression);
    }

    /**
     * Helper method to process given search terms and create criteria about
     * those. Note that each repository has 'searchColumns' property which
     * contains the fields where search term will be affected.
     *
     * @see \App\Repository\Traits\RepositoryMethodsTrait::getQueryBuilder()
     *
     * @param array<int, string> $columns Entity fields searched by each term.
     * @param array<string, array<int, string>|string>|null $terms Search terms indexed by their AND/OR operand.
     *
     * @throws InvalidArgumentException
     */
    public static function processSearchTerms(QueryBuilder $queryBuilder, array $columns, ?array $terms = null): void
    {
        $terms ??= [];

        if ($columns === []) {
            return;
        }

        // Iterate search term sets
        foreach ($terms as $operand => $search) {
            $criteria = SearchTerm::getCriteria($columns, $search, $operand);

            if ($criteria !== null) {
                $queryBuilder->andWhere(self::getExpression($queryBuilder, $queryBuilder->expr()->andX(), $criteria));
            }
        }
    }

    /**
     * Simple process method for order by part of for current query builder.
     *
     * @param array<string, string|SortDirection>|null $orderBy Sort directions indexed by entity field;
     *                                                          null adds no sorting.
     */
    public static function processOrderBy(QueryBuilder $queryBuilder, ?array $orderBy = null): void
    {
        $orderBy ??= [];

        foreach ($orderBy as $column => $order) {
            if (!str_contains($column, '.')) {
                $column = 'entity.' . $column;
            }

            if ($order instanceof SortDirection) {
                $sortDirection = $order;
            } else {
                $sortDirection = match (strtolower($order)) {
                    'asc' => SortDirection::Ascending,
                    'desc' => SortDirection::Descending,
                    default => throw new InvalidArgumentException(sprintf('Invalid sort direction "%s".', $order)),
                };
            }

            $queryBuilder->addOrderBy($column, $sortDirection);
        }
    }

    /**
     * Recursively takes the specified criteria and adds too the expression.
     *
     * The criteria is defined in an array notation where each item in the list
     * represents a comparison <fieldName, operator, value>. The operator maps
     * to comparison methods located in ExpressionBuilder. The key in the array
     * can be used to identify grouping of comparisons.
     *
     * Currently supported Doctrine\ORM\Query\Expr methods:
     *
     * OPERATOR EXAMPLE INPUT ARRAY GENERATED QUERY RESULT NOTES
     *  eq ['u.id', 'eq', 123] u.id = ?1 -
     *  neq ['u.id', 'neq', 123] u.id <> ?1 -
     *  lt ['u.id', 'lt', 123] u.id < ?1 -
     *  lte ['u.id', 'lte', 123] u.id <= ?1 -
     *  gt ['u.id', 'gt', 123] u.id > ?1 -
     *  gte ['u.id', 'gte', 123] u.id >= ?1 -
     *  in ['u.id', 'in', [1,2]] u.id IN (1,2) third value may contain n values
     *  notIn ['u.id', 'notIn', [1,2]] u.id NOT IN (1,2) third value may contain n values
     *  isNull ['u.id', 'isNull', null] u.id IS NULL third value must be set, but not used
     *  isNotNull ['u.id', 'isNotNull', null] u.id IS NOT NULL third value must be set, but not used
     *  like ['u.id', 'like', 'abc'] u.id LIKE ?1 -
     *  notLike ['u.id', 'notLike', 'abc'] u.id NOT LIKE ?1 -
     *  between ['u.id', 'between', [1,6]] u.id BETWEEN ?1 AND ?2 third value must contain two values
     *
     * Also note that you can easily combine 'and' and 'or' queries like
     * following examples:
     *
     * EXAMPLE INPUT ARRAY GENERATED QUERY RESULT
     *  [
     *      'and' => [
     *          ['u.firstName', 'eq', 'foo bar']
     *          ['u.lastName', 'neq', 'not this one']
     *      ]
     *  ] (u.firstName = ?1 AND u.lastName <> ?2)
     *  [
     *      'or' => [
     *          ['u.firstName', 'eq', 'foo bar']
     *          ['u.lastName', 'neq', 'not this one']
     *      ]
     *  ] (u.firstName = ?1 OR u.lastName <> ?2)
     *
     * Also note that you can nest these criteria arrays as many levels as you
     * need - only the sky is the limit...
     *
     * @example
     *  $criteria = [
     *      'or' => [
     *          ['entity.field1', 'like', '%field1Value%'],
     *          ['entity.field2', 'like', '%field2Value%'],
     *      ],
     *      'and' => [
     *          ['entity.field3', 'eq', 3],
     *          ['entity.field4', 'eq', 'four'],
     *      ],
     *      ['entity.field5', 'neq', 5],
     *  ];
     *
     * $qb = $this->createQueryBuilder('entity');
     * $qb->where($this->getExpression($qb, $qb->expr()->andX(), $criteria));
     * $query = $qb->getQuery();
     * echo $query->getSQL();
     *
     * // Result:
     * // SELECT *
     * // FROM tableName
     * // WHERE ((field1 LIKE '%field1Value%') OR (field2 LIKE '%field2Value%'))
     * // AND ((field3 = '3') AND (field4 = 'four'))
     * // AND (field5 <> '5')
     *
     * Also note that you can nest these queries as many times as you wish...
     *
     * @see https://gist.github.com/jgornick/8671644
     *
     * @param array<int|string, mixed> $criteria Comparison tuples and nested AND/OR groups to append.
     *
     * @throws InvalidArgumentException
     */
    public static function getExpression(
        QueryBuilder $queryBuilder,
        Composite $expression,
        array $criteria,
    ): Composite {
        self::processExpression($queryBuilder, $expression, Group::fromArray($criteria));

        return $expression;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function processExpression(QueryBuilder $queryBuilder, Composite $expression, Group $criteria): void
    {
        foreach ($criteria->children as $comparison) {
            if ($comparison instanceof Group) {
                $nestedExpression = $comparison->conjunction
                    ? $queryBuilder->expr()->andX()
                    : $queryBuilder->expr()->orX();

                self::processExpression($queryBuilder, $nestedExpression, $comparison);

                $expression->add($nestedExpression);
            } else {
                self::buildExpression($queryBuilder, $expression, $comparison);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function buildExpression(
        QueryBuilder $queryBuilder,
        Composite $expression,
        Comparison $comparison,
    ): void {
        $parameters = self::determineComparisonAndParameters($queryBuilder, $comparison);

        /** @var callable(string|array<array-key, Literal>...): (string|Stringable) $callable */
        $callable = [$queryBuilder->expr(), $comparison->operator];

        $expression->add(call_user_func_array($callable, $parameters));
    }

    /**
     * @return ExpressionParameters
     *
     * @throws InvalidArgumentException
     */
    private static function determineComparisonAndParameters(QueryBuilder $queryBuilder, Comparison $comparison): array
    {
        // Increase parameter count
        self::$parameterCount++;

        // Initialize used callback parameters
        $parameters = [$comparison->field];

        $lowercaseOperator = strtolower($comparison->operator);

        if ($lowercaseOperator !== 'isnull' && $lowercaseOperator !== 'isnotnull') {
            $parameters = self::getComparisonParameters(
                $queryBuilder,
                $comparison,
                $lowercaseOperator,
                $parameters,
            );
        }

        return $parameters;
    }

    /**
     * @param list<string> $parameters Expression arguments already containing the field name.
     * @param array<array-key, Operand> $value BETWEEN bounds or the elements of an IN/NOT IN comparison.
     *
     * @return ExpressionParameters
     */
    private static function getParameters(
        QueryBuilder $queryBuilder,
        string $lowercaseOperator,
        array $parameters,
        array $value,
    ): array {
        // Operator is between, so we need to add third parameter for Expr method
        if ($lowercaseOperator === 'between') {
            $parameters[] = '?' . self::$parameterCount;
            $queryBuilder->setParameter(self::$parameterCount, $value[0], UuidHelper::getType((string)$value[0]));

            self::$parameterCount++;

            $parameters[] = '?' . self::$parameterCount;
            $queryBuilder->setParameter(self::$parameterCount, $value[1], UuidHelper::getType((string)$value[1]));
        } else {
            // Otherwise this must be IN or NOT IN expression
            $value = array_map(
                static fn (string|int|float|bool|Stringable $element): string => (string)$element,
                $value,
            );

            try {
                $value = array_map(static fn (string $value): string => UuidHelper::getBytes($value), $value);
            } catch (InvalidUuidStringException $exception) {
                // Ok so value isn't list of UUIDs
                syslog(LOG_INFO, $exception->getMessage());
            }

            $parameters[] = array_map(
                static fn (string $value): Literal => $queryBuilder->expr()
                    ->literal(
                        is_numeric($value) ? (int)$value : $value,
                    ),
                $value,
            );
        }

        return $parameters;
    }

    /**
     * @param list<string> $parameters Expression arguments already containing the field name.
     *
     * @return ExpressionParameters
     */
    private static function getComparisonParameters(
        QueryBuilder $queryBuilder,
        Comparison $comparison,
        string $lowercaseOperator,
        array $parameters,
    ): array {
        if (is_array($comparison->value)) {
            $value = $comparison->value;

            $parameters = self::getParameters($queryBuilder, $lowercaseOperator, $parameters, $value);
        } else {
            $parameters[] = '?' . self::$parameterCount;

            $queryBuilder->setParameter(
                self::$parameterCount,
                $comparison->value,
                UuidHelper::getType((string)$comparison->value),
            );
        }

        return $parameters;
    }
}
