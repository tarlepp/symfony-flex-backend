<?php
declare(strict_types = 1);
/**
 * /src/Enum/Interfaces/LabeledEnumInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum\Interfaces;

/**
 * Interface for enums that have a label representation.
 *
 * @package App\Enum\Interfaces
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface LabeledEnumInterface
{
    /**
     * Get the label for this enum case.
     */
    public function label(): string;
}
