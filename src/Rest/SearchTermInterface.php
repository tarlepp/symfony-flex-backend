<?php
declare(strict_types = 1);
/**
 * /src/Rest/SearchTermInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
    const OPERAND_OR = 'or';
    const OPERAND_AND = 'and';

    // Used MODE constants
    const MODE_STARTS_WITH = 1;
    const MODE_ENDS_WITH = 2;
    const MODE_FULL = 3;
}
