<?php
declare(strict_types = 1);

/**
 * /src/Enum/Interfaces/StringEnumInterface.php
 */

namespace App\Enum\Interfaces;

use BackedEnum;

/**
 * Enum StringEnumInterface
 */
interface StringEnumInterface extends BackedEnum
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array;
}
