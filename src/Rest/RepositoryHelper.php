<?php
declare(strict_types = 1);
/**
 * /src/Rest/RepositoryHelper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use Closure;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Literal;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use stdClass;
use Throwable;
use function array_combine;
use function array_key_exists;
use function array_map;
use function array_walk;
use function call_user_func_array;
use function count;
use function is_array;
use function is_numeric;
use function strcmp;
use function strpos;
use function strtolower;

/**
 * Class RepositoryHelper
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
     * @see \App\Rest\Repository::getExpression()
     * @see \App\Controller\Rest::processCriteria()
     *
     * @param array<int|string, string|array>|null $criteria
     *
     * @throws InvalidArgumentException
     */
    public static function processCriteria(QueryBuilder $queryBuilder, ?array $criteria = null): void
    {
        $criteria ??= [];

        if (count($criteria) === 0) {
            return;
        }

        // Initialize condition array
        $condition = [];

        // Create used condition array
        array_walk($criteria, self::getIterator($condition));

        // And attach search term condition to main query
        $queryBuilder->andWhere(self::getExpression($queryBuilder, $queryBuilder->expr()->andX(), $condition));
    }

    /**
     * Helper method to process given search terms and create criteria about
     * those. Note that each repository has 'searchColumns' property which
     * contains the fields where search term will be affected.
     *
     * @see \App\Controller\Rest::getSearchTerms
     *
     * @param array<int, string> $columns
     * @param array<string, string>|null $terms
     *
     * @throws InvalidArgumentException
     */
    public static function processSearchTerms(QueryBuilder $queryBuilder, array $columns, ?array $terms = null): void
    {
        $terms ??= [];

        if (count($columns) === 0) {
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
     * @param array<string, string>|null $orderBy
     */
    public static function processOrderBy(QueryBuilder $queryBuilder, ?array $orderBy = null): void
    {
        $orderBy ??= [];

        foreach ($orderBy as $column => $order) {
            if (strpos($column, '.') === false) {
                $column = 'entity.' . $column;
            }

            $queryBuilder->addOrderBy((string)$column, $order);
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
     * @param array<int|string, string|array> $criteria
     *
     * @throws InvalidArgumentException
     */
    public static function getExpression(
        QueryBuilder $queryBuilder,
        Composite $expression,
        array $criteria
    ): Composite {
        self::processExpression($queryBuilder, $expression, $criteria);

        return $expression;
    }

    /**
     * @param array<int|string, string|array> $criteria
     *
     * @throws InvalidArgumentException
     */
    private static function processExpression(QueryBuilder $queryBuilder, Composite $expression, array $criteria): void
    {
        $iterator = static function (array $comparison, string $key) use ($queryBuilder, $expression): void {
            $expressionAnd = ($key === 'and' || array_key_exists('and', $comparison));
            $expressionOr = ($key === 'or' || array_key_exists('or', $comparison));

            self::buildExpression($queryBuilder, $expression, $expressionAnd, $expressionOr, $comparison);
        };

        array_walk($criteria, $iterator);
    }

    /**
     * @param array<int|string, string|array> $comparison
     *
     * @throws InvalidArgumentException
     */
    private static function buildExpression(
        QueryBuilder $queryBuilder,
        Composite $expression,
        bool $expressionAnd,
        bool $expressionOr,
        $comparison
    ): void {
        if ($expressionAnd) {
            $expression->add(self::getExpression($queryBuilder, $queryBuilder->expr()->andX(), $comparison));
        } elseif ($expressionOr) {
            $expression->add(self::getExpression($queryBuilder, $queryBuilder->expr()->orX(), $comparison));
        } else {
            [$comparison, $parameters] = self::determineComparisonAndParameters($queryBuilder, $comparison);

            /** @var callable $callable */
            $callable = [$queryBuilder->expr(), $comparison->operator];

            // And finally add new expression to main one with specified parameters
            $expression->add(call_user_func_array($callable, $parameters));
        }
    }

    /**
     * Lambda function to create condition array for 'getExpression' method.
     *
     * @param string|array<int, string> $value
     *
     * @return array<int, string|array>
     */
    private static function createCriteria(string $column, $value): array
    {
        if (strpos($column, '.') === false) {
            $column = 'entity.' . $column;
        }

        $operator = is_array($value) ? 'in' : 'eq';

        return [$column, $operator, $value];
    }

    /**
     * @param array<int|string, string|array> $comparison
     *
     * @return array<int, mixed>
     */
    private static function determineComparisonAndParameters(QueryBuilder $queryBuilder, array $comparison): array
    {
        $comparisonObject = (object)array_combine(['field', 'operator', 'value'], $comparison);

        // Increase parameter count
        self::$parameterCount++;

        // Initialize used callback parameters
        $parameters = [$comparisonObject->field];

        $lowercaseOperator = strtolower($comparisonObject->operator);

        if (!($lowercaseOperator === 'isnull' || $lowercaseOperator === 'isnotnull')) {
            $parameters = self::getComparisonParameters(
                $queryBuilder,
                $comparisonObject,
                $lowercaseOperator,
                $parameters
            );
        }

        return [$comparisonObject, $parameters];
    }

    /**
     * @param array<int, string> $parameters
     * @param array<int, mixed> $value
     *
     * @return array<int, array<int, Literal>|string>
     */
    private static function getParameters(
        QueryBuilder $queryBuilder,
        string $lowercaseOperator,
        array $parameters,
        array $value
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
            try {
                $value = array_map([UuidHelper::class, 'getBytes'], $value);
            } catch (InvalidUuidStringException $exception) {
                (static fn (Throwable $exception): Throwable => $exception)($exception);
            }

            $parameters[] = array_map(
                static fn (string $value): Literal => $queryBuilder->expr()->literal(is_numeric($value)
                    ? (int)$value
                    : $value),
                $value
            );
        }

        return $parameters;
    }

    /**
     * @param array<int|string, string|array> $condition
     *
     * @psalm-suppress MissingClosureParamType
     */
    private static function getIterator(array &$condition): Closure
    {
        return static function ($value, string $column) use (&$condition): void {
            // If criteria contains 'and' OR 'or' key(s) assume that array in only in the right format
            if (strcmp($column, 'and') === 0 || strcmp($column, 'or') === 0) {
                $condition[$column] = $value;
            } else {
                // Add condition
                $condition[] = self::createCriteria($column, $value);
            }
        };
    }

    /**
     * @param array<int, string> $parameters
     *
     * @return array<int, array<int, Literal>|string>
     */
    private static function getComparisonParameters(
        QueryBuilder $queryBuilder,
        stdClass $comparison,
        string $lowercaseOperator,
        array $parameters
    ): array {
        if (is_array($comparison->value)) {
            $value = $comparison->value;

            $parameters = self::getParameters($queryBuilder, $lowercaseOperator, $parameters, $value);
        } else {
            $parameters[] = '?' . self::$parameterCount;

            $queryBuilder->setParameter(
                self::$parameterCount,
                $comparison->value,
                UuidHelper::getType((string)$comparison->value)
            );
        }

        return $parameters;
    }
}
