<?php
declare(strict_types = 1);

/**
 * /src/Enum/Interfaces/LabeledEnumInterface.php
 */

namespace App\Enum\Interfaces;

/**
 * Interface for enums that have a label representation.
 */
interface LabeledEnumInterface
{
    /**
     * Get the label for this enum case.
     */
    public function label(): string;
}
