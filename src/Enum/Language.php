<?php
declare(strict_types = 1);

/**
 * /src/Enum/Language.php
 */

namespace App\Enum;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Interfaces\EnumWithDefaultInterface;
use App\Enum\Traits\GetValues;
use Override;

/**
 * Language enum
 */
enum Language: string implements DatabaseEnumInterface, EnumWithDefaultInterface
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
