<?php
declare(strict_types = 1);
/**
 * /src/Rest/SearchTerm.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use App\Rest\Interfaces\SearchTermInterface;
use Closure;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function is_array;
use function strpos;
use function trim;

/**
 * Class SearchTerm
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class SearchTerm implements SearchTermInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCriteria($column, $search, ?string $operand = null, ?int $mode = null): ?array
    {
        $operand ??= self::OPERAND_OR;
        $mode ??= self::MODE_FULL;

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
     * @param array<int, string> $columns
     * @param array<int, string> $searchTerms
     *
     * @return array<string, array<string, array>>|null
     */
    private static function createCriteria(array $columns, array $searchTerms, string $operand, int $mode): ?array
    {
        $iteratorTerm = self::getTermIterator($columns, $mode);

        /**
         * Get criteria
         *
         * @var array<string, array<string, array>>
         */
        $criteria = array_filter(array_map($iteratorTerm, $searchTerms));

        // Initialize output
        $output = null;

        // We have some generated criteria
        if (count($criteria) > 0) {
            // Create used criteria array
            $output = [
                'and' => [
                    $operand => array_merge(...array_values($criteria)),
                ],
            ];
        }

        return $output;
    }

    /**
     * Method to get term iterator closure.
     *
     * @param array<int, string> $columns
     */
    private static function getTermIterator(array $columns, int $mode): Closure
    {
        return static fn (string $term): ?array => count($columns) > 0
            ? array_map(self::getColumnIterator($term, $mode), $columns)
            : null;
    }

    /**
     * Method to get column iterator closure.
     */
    private static function getColumnIterator(string $term, int $mode): Closure
    {
        /*
         * Lambda function to create actual criteria for specified column + term + mode combo.
         *
         * @param string $column
         *
         * @return array<int, string>
         */
        return static fn (string $column): array => [
            strpos($column, '.') === false ? 'entity.' . $column : $column, 'like', self::getTerm($mode, $term),
        ];
    }

    /**
     * Method to get search term clause for 'LIKE' query for specified mode.
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
     * @param string|array<int, string> $column search column(s), could be a
     *                                          string or an array of strings
     *
     * @return array<int, string>
     */
    private static function getColumns($column): array
    {
        // Normalize column and search parameters
        return array_filter(
            array_map('trim', (is_array($column) ? $column : (array)$column)),
            static fn (string $value): bool => trim($value) !== ''
        );
    }

    /**
     * Method to get search terms.
     *
     * @param string|array<int, string>|null $search search term(s), could be
     *                                               a string or an array of
     *                                               strings
     *
     * @return array<int, string>
     */
    private static function getSearchTerms($search): array
    {
        return array_unique(
            array_filter(
                array_map('trim', (is_array($search) ? $search : explode(' ', (string)$search))),
                static fn (string $value): bool => trim($value) !== ''
            )
        );
    }
}
