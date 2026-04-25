<?php
declare(strict_types = 1);
/**
 * /src/Enum/Interfaces/EnumWithDefaultInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum\Interfaces;

/**
 * Interface for enums that provide a default value.
 *
 * @package App\Enum\Interfaces
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface EnumWithDefaultInterface
{
    /**
     * Get the default enum case.
     */
    public static function getDefault(): self;
}

