<?php
declare(strict_types = 1);
/**
 * /src/Enum/Locale.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Interfaces\EnumWithDefaultInterface;
use App\Enum\Traits\GetValues;
use Override;

/**
 * Locale enum
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Locale: string implements DatabaseEnumInterface, EnumWithDefaultInterface
{
    use GetValues;

    case EN = 'en';
    case FI = 'fi';

    #[Override]
    public static function getDefault(): self
    {
        return self::EN;
    }
}
