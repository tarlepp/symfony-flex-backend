<?php
declare(strict_types = 1);
/**
 * /src/Rest/Interfaces/SearchTermInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Interfaces;

/**
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface SearchTermInterface
{
    // @codeCoverageIgnoreStart
    // Used OPERAND constants
    public const string OPERAND_OR = 'or';
    public const string OPERAND_AND = 'and';

    // Used MODE constants
    public const int MODE_STARTS_WITH = 1;
    public const int MODE_ENDS_WITH = 2;
    public const int MODE_FULL = 3;
    // @codeCoverageIgnoreEnd

    /**
     * Static method to get search term criteria for specified columns and search terms with specified operand and mode.
     *
     * @codeCoverageIgnore This is needed because variables are multiline
     *
     * @param string|array<int, string> $column  search column(s), could be a string or an array of strings
     * @param string|array<int, string> $search  search term(s), could be a string or an array of strings
     * @param string|null               $operand Used operand with multiple search terms. See OPERAND_* constants.
     *                                           Defaults to self::OPERAND_OR
     * @param int|null                  $mode    Used mode on LIKE search. See MODE_* constants. Defaults to
     *                                           self::MODE_FULL
     *
     * @return array<string, array<string, array<string, mixed>>>|null
     */
    public static function getCriteria(
        array | string $column,
        array | string $search,
        ?string $operand = null,
        ?int $mode = null,
    ): ?array;
}
