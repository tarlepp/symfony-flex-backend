<?php
declare(strict_types = 1);

/**
 * /src/Enum/Locale.php
 */

namespace App\Enum;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Interfaces\EnumWithDefaultInterface;
use App\Enum\Traits\GetValues;
use Override;

/**
 * Locale enum
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
