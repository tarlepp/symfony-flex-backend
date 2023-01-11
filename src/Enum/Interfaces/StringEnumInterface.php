<?php
declare(strict_types = 1);
/**
 * /src/Enum/Interfaces/StringEnumInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum\Interfaces;

use BackedEnum;

/**
 * Enum StringEnumInterface
 *
 * @package App\Enum\Interfaces
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface StringEnumInterface extends BackedEnum
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array;
}
