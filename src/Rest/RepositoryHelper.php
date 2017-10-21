<?php
declare(strict_types = 1);
/**
 * /src/Rest/RepositoryHelper.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;

/**
 * Class RepositoryHelper
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RepositoryHelper
{
    /**
     * Parameter count in current query, this is used to track parameters which are bind to current query.
     *
     * @var integer
     */
    public static $parameterCount = 0;

    /**
     * Method to reset current parameter count value
     */
    public static function resetParameterCount(): void
    {
        self::$parameterCount = 0;
    }

    /**
     * Process given criteria which is given by ?where parameter. This is given as JSON string, which is converted
     * to assoc array for this process.
     *
     * Note that this supports by default (without any extra work) just 'eq' and 'in' expressions. See example array
     * below:
     *
     *  [
     *      'u.id'  => 3,
     *      'u.uid' => 'uid',
     *      'u.foo' => [1, 2, 3],
     *      'u.bar' => ['foo', 'bar'],
     *  ]
     *
     * And these you can make easily happen within REST controller and simple 'where' parameter. See example below:
     *
     *  ?where={"u.id":3,"u.uid":"uid","u.foo":[1,2,3],"u.bar":["foo","bar"]}
     *
     * Also note that you can make more complex use case fairly easy, just follow instructions below.
     *
     * If you're trying to make controller specified special criteria with projects generic Rest controller, just
     * add 'processCriteria(array &$criteria)' method to your own controller and pre-process that criteria in there
     * the way you want it to be handled. In other words just modify that basic key-value array just as you like it,
     * main goal is to create array that is compatible with 'getExpression' method in this class. For greater detail
     * just see that method comments.
     *
     * tl;dr Modify your $criteria parameter in your controller with 'processCriteria(array &$criteria)' method.
     *
     * @see \App\Rest\Repository::getExpression
     * @see \App\Controller\Rest::processCriteria
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $criteria
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public static function processCriteria(QueryBuilder $queryBuilder, array $criteria): void
    {
        if (empty($criteria)) {
            return;
        }

        // Initialize condition array
        $condition = [];

        /**
         * Lambda function to create condition array for 'getExpression' method.
         *
         * @param string      $column
         * @param mixed       $value
         * @param null|string $operator
         *
         * @return array
         */
        $createCriteria = function (string $column, $value, $operator = null) {
            if (\strpos($column, '.') === false) {
                $column = 'entity.' . $column;
            }

            // Determine used operator
            if ($operator === null) {
                $operator = \is_array($value) ? 'in' : 'eq';
            }

            return [$column, $operator, $value];
        };

        /**
         * Lambda function to process criteria and add it to main condition array.
         *
         * @param mixed  $value
         * @param string $column
         */
        $processCriteria = function ($value, $column) use (&$condition, $createCriteria) {
            // If criteria contains 'and' OR 'or' key(s) assume that array in only in the right format
            if (\strcmp($column, 'and') === 0 || \strcmp($column, 'or') === 0) {
                $condition[$column] = $value;
            } else {
                // Add condition
                $condition[] = $createCriteria($column, $value);
            }
        };

        // Create used condition array
        \array_walk($criteria, $processCriteria);

        // And attach search term condition to main query
        $queryBuilder->andWhere(self::getExpression($queryBuilder, $queryBuilder->expr()->andX(), $condition));
    }

    /**
     * Helper method to process given search terms and create criteria about those. Note that each repository
     * has 'searchColumns' property which contains the fields where search term will be affected.
     *
     * @see \App\Controller\Rest::getSearchTerms
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $terms
     * @param array        $columns
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public static function processSearchTerms(QueryBuilder $queryBuilder, array $terms, array $columns): void
    {
        if (empty($columns)) {
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
     * @param QueryBuilder $queryBuilder
     * @param array        $orderBy
     *
     * @return void
     */
    public static function processOrderBy(QueryBuilder $queryBuilder, array $orderBy): void
    {
        foreach ($orderBy as $column => $order) {
            if (\strpos($column, '.') === false) {
                $column = 'entity.' . $column;
            }

            $queryBuilder->addOrderBy($column, $order);
        }
    }

    /**
     * Recursively takes the specified criteria and adds too the expression.
     *
     * The criteria is defined in an array notation where each item in the list
     * represents a comparison <fieldName, operator, value>. The operator maps to
     * comparison methods located in ExpressionBuilder. The key in the array can
     * be used to identify grouping of comparisons.
     *
     * Currently supported  Doctrine\ORM\Query\Expr methods:
     *
     * OPERATOR    EXAMPLE INPUT ARRAY             GENERATED QUERY RESULT      NOTES
     *  eq          ['u.id',  'eq',        123]     u.id = ?1                   -
     *  neq         ['u.id',  'neq',       123]     u.id <> ?1                  -
     *  lt          ['u.id',  'lt',        123]     u.id < ?1                   -
     *  lte         ['u.id',  'lte',       123]     u.id <= ?1                  -
     *  gt          ['u.id',  'gt',        123]     u.id > ?1                   -
     *  gte         ['u.id',  'gte',       123]     u.id >= ?1                  -
     *  in          ['u.id',  'in',        [1,2]]   u.id IN (1,2)               third value may contain n values
     *  notIn       ['u.id',  'notIn',     [1,2]]   u.id NOT IN (1,2)           third value may contain n values
     *  isNull      ['u.id',  'isNull',    null]    u.id IS NULL                third value must be set, but not used
     *  isNotNull   ['u.id',  'isNotNull', null]    u.id IS NOT NULL            third value must be set, but not used
     *  like        ['u.id',  'like',      'abc']   u.id LIKE ?1                -
     *  notLike     ['u.id',  'notLike',   'abc']   u.id NOT LIKE ?1            -
     *  between     ['u.id',  'between',  [1,6]]    u.id BETWEEN ?1 AND ?2      third value must contain two values
     *
     * Also note that you can easily combine 'and' and 'or' queries like following examples:
     *
     * EXAMPLE INPUT ARRAY                                  GENERATED QUERY RESULT
     *  [
     *      'and' => [
     *          ['u.firstname', 'eq',   'foo bar']
     *          ['u.surname',   'neq',  'not this one']
     *      ]
     *  ]                                                   (u.firstname = ?1 AND u.surname <> ?2)
     *  [
     *      'or' => [
     *          ['u.firstname', 'eq',   'foo bar']
     *          ['u.surname',   'neq',  'not this one']
     *      ]
     *  ]                                                   (u.firstname = ?1 OR u.surname <> ?2)
     *
     * Also note that you can nest these criteria arrays as many levels as you need - only the sky is the limit...
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
     * @param QueryBuilder $queryBuilder
     * @param Composite    $expression
     * @param array        $criteria
     *
     * @return Composite
     *
     * @throws \InvalidArgumentException
     */
    public static function getExpression(
        QueryBuilder $queryBuilder,
        Composite $expression,
        array $criteria
    ): Composite {
        if (\count($criteria)) {
            foreach ($criteria as $key => $comparison) {
                if ($key === 'and' || \array_key_exists('and', $comparison)) {
                    $expression->add(self::getExpression($queryBuilder, $queryBuilder->expr()->andX(), $comparison));
                } elseif ($key === 'or' || \array_key_exists('or', $comparison)) {
                    $expression->add(self::getExpression($queryBuilder, $queryBuilder->expr()->orX(), $comparison));
                } else {
                    $comparison = (object)\array_combine(['field', 'operator', 'value'], $comparison);

                    // Increase parameter count
                    self::$parameterCount++;

                    // Initialize used callback parameters
                    $parameters = [$comparison->field];

                    $lowercaseOperator = \strtolower($comparison->operator);

                    // Array values needs some extra work
                    if (\is_array($comparison->value)) {
                        /** @var array $value */
                        $value = $comparison->value;

                        // Operator is between, so we need to add third parameter for Expr method
                        if ($lowercaseOperator === 'between') {
                            $parameters[] = '?' . self::$parameterCount;
                            $queryBuilder->setParameter(self::$parameterCount, $value[0]);

                            self::$parameterCount++;

                            $parameters[] = '?' . self::$parameterCount;
                            $queryBuilder->setParameter(self::$parameterCount, $value[1]);
                        } else { // Otherwise this must be IN or NOT IN expression
                            $parameters[] = \array_map(function ($value) use ($queryBuilder) {
                                return $queryBuilder->expr()->literal($value);
                            }, $value);
                        }
                    } elseif (!($lowercaseOperator === 'isnull' || $lowercaseOperator === 'isnotnull')) {
                        $parameters[] = '?' . self::$parameterCount;

                        $queryBuilder->setParameter(self::$parameterCount, $comparison->value);
                    }

                    // And finally add new expression to main one with specified parameters
                    $expression->add(
                        \call_user_func_array([$queryBuilder->expr(), $comparison->operator], $parameters)
                    );
                }
            }
        }

        return $expression;
    }
}
