<?php
declare(strict_types = 1);
/**
 * /src/Enum/Locale.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Traits\GetValues;

/**
 * Locale enum
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Locale: string implements DatabaseEnumInterface
{
    use GetValues;

    case EN = 'en';
    case FI = 'fi';

    public static function getDefault(): self
    {
        return self::EN;
    }
}
