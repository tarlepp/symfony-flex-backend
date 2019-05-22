<?php
declare(strict_types = 1);
/**
 * /src/Rest/SearchTermInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

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
}
