<?php
declare(strict_types = 1);
/**
 * /src/Enum/Traits/GetValues.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum\Traits;

use function array_column;

/**
 * Trait GetValues
 *
 * @package App\Enum\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait GetValues
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
