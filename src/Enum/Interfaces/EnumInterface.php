<?php
declare(strict_types = 1);
/**
 * /src/Enum/Interfaces/EnumInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum\Interfaces;

use BackedEnum;

/**
 * Enum EnumInterface
 *
 * @package App\Enum\Interfaces
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface EnumInterface extends BackedEnum
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array;
}
