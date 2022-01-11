<?php
declare(strict_types = 1);
/**
 * /src/Enum/Traits/GetValues.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum\Traits;

use function array_map;

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
        return array_map(static fn (self $enum): string => $enum->value, self::cases());
    }
}
