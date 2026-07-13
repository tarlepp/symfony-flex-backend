<?php
declare(strict_types = 1);

/**
 * /src/Enum/Interfaces/EnumWithDefaultInterface.php
 */

namespace App\Enum\Interfaces;

/**
 * Interface for enums that provide a default value.
 */
interface EnumWithDefaultInterface
{
    /**
     * Get the default enum case.
     */
    public static function getDefault(): self;
}
