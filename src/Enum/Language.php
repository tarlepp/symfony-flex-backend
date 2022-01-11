<?php
declare(strict_types = 1);
/**
 * /src/Enum/Language.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

// phpcs:ignoreFile

namespace App\Enum;

use App\Enum\Interfaces\EnumInterface;
use App\Enum\Traits\GetValues;

/**
 * Enum Language
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Language: string implements EnumInterface
{
    use GetValues;

    case EN = 'en';
    case FI = 'fi';

    public static function getDefault(): self
    {
        return self::EN;
    }
}
