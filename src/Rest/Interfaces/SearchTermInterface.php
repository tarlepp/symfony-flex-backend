<?php
declare(strict_types = 1);
/**
 * /src/Rest/Interfaces/SearchTermInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Interfaces;

/**
 * Interface SearchTermInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface SearchTermInterface
{
    // Used OPERAND constants
    public const OPERAND_OR = 'or';
    public const OPERAND_AND = 'and';

    // Used MODE constants
    public const MODE_STARTS_WITH = 1;
    public const MODE_ENDS_WITH = 2;
    public const MODE_FULL = 3;

    /**
     * Static method to get search term criteria for specified columns and search terms with specified operand and mode.
     *
     * @param string|string[] $column  Search column(s), could be a string or an array of strings.
     * @param string|string[] $search  Search term(s), could be a string or an array of strings.
     * @param string|null     $operand Used operand with multiple search terms. See OPERAND_* constants. Defaults
     *                                 to self::OPERAND_OR
     * @param int|null        $mode    Used mode on LIKE search. See MODE_* constants. Defaults to self::MODE_FULL
     *
     * @return mixed[]|null
     */
    public static function getCriteria($column, $search, ?string $operand = null, ?int $mode = null): ?array;
}
