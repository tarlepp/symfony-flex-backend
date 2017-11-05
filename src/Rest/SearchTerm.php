<?php
declare(strict_types = 1);
/**
 * /src/Rest/SearchTerm.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

/**
 * Class SearchTerm
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class SearchTerm implements SearchTermInterface
{
    /**
     * Static method to get search term criteria for specified columns and search terms with specified operand and mode.
     *
     * @param string|array $column  Search column(s), could be a string or an array of strings.
     * @param string|array $search  Search term(s), could be a string or an array of strings.
     * @param null|string  $operand Used operand with multiple search terms. See OPERAND_* constants. Defaults
     *                              to self::OPERAND_OR
     * @param null|integer $mode    Used mode on LIKE search. See MODE_* constants. Defaults to self::MODE_FULL
     *
     * @return array|null
     */
    public static function getCriteria($column, $search, string $operand = null, int $mode = null): ?array
    {
        $operand = $operand ?? self::OPERAND_OR;
        $mode = $mode ?? self::MODE_FULL;

        $columns = self::getColumns($column);
        $searchTerms = self::getSearchTerms($search);

        // Fallback to OR operand if not supported one given
        if ($operand !== self::OPERAND_AND && $operand !== self::OPERAND_OR) {
            $operand = self::OPERAND_OR;
        }

        return self::createCriteria($columns, $searchTerms, $operand, $mode);
    }

    /**
     * Helper method to create used criteria array with given columns and search terms.
     *
     * @param array   $columns
     * @param array   $searchTerms
     * @param string  $operand
     * @param integer $mode
     *
     * @return array|null
     */
    private static function createCriteria(array $columns, array $searchTerms, string $operand, int $mode): ?array
    {
        $iteratorTerm = self::getTermIterator($columns, $mode);

        // Get criteria
        $criteria = \array_filter(\array_map($iteratorTerm, $searchTerms));

        // Initialize output
        $output = null;

        // We have some generated criteria
        if (\count($criteria)) {
            // Create used criteria array
            $output = [
                'and' => [
                    $operand => \array_merge(...\array_values($criteria))
                ]
            ];
        }

        return $output;
    }

    /**
     * Method to get term iterator closure.
     *
     * @param array $columns
     * @param int   $mode
     *
     * @return \Closure
     */
    private static function getTermIterator(array $columns, int $mode): \Closure
    {
        /**
         * Lambda function to process each search term to specified search columns.
         *
         * @param string $term
         *
         * @return array
         */
        $iteratorTerm = function (string $term) use ($columns, $mode): ?array {
            return \count($columns) ? \array_map(self::getColumnIterator($term, $mode), $columns) : null;
        };

        return $iteratorTerm;
    }

    /**
     * Method to get column iterator closure.
     *
     * @param string $term
     * @param int    $mode
     *
     * @return \Closure
     */
    private static function getColumnIterator(string $term, int $mode): \Closure
    {
        /**
         * Lambda function to create actual criteria for specified column + term + mode combo.
         *
         * @param string $column
         *
         * @return array
         */
        $iteratorColumn = function (string $column) use ($term, $mode): array {
            if (\strpos($column, '.') === false) {
                $column = 'entity.' . $column;
            }

            return [$column, 'like', self::getTerm($mode, $term)];
        };

        return $iteratorColumn;
    }

    /**
     * Method to get search term clause for 'LIKE' query for specified mode.
     *
     * @param int    $mode
     * @param string $term
     *
     * @return string
     */
    private static function getTerm(int $mode, string $term): string
    {
        switch ($mode) {
            case self::MODE_STARTS_WITH:
                $term .= '%';
                break;
            case self::MODE_ENDS_WITH:
                $term = '%' . $term;
                break;
            case self::MODE_FULL:
            default:
                $term = '%' . $term . '%';
                break;
        }

        return $term;
    }

    /**
     * @param $column
     *
     * @return array
     */
    private static function getColumns($column): array
    {
        $filter = function (string $value): bool {
            return \mb_strlen(\trim($value)) > 0;
        };

        // Normalize column and search parameters
        $columns = \array_filter(
            \array_map('\trim', (\is_array($column) ? $column : (array)$column)),
            $filter
        );

        return $columns;
    }

    /**
     * Method to get search terms.
     *
     * @param array|string $search
     *
     * @return array
     */
    private static function getSearchTerms($search): array
    {
        $filter = function (string $value): bool {
            return \mb_strlen(\trim($value)) > 0;
        };

        $searchTerms = \array_unique(
            \array_filter(
                \array_map('\trim', (\is_array($search) ? $search : \explode(' ', (string)$search))),
                $filter
            )
        );
        return $searchTerms;
    }
}
