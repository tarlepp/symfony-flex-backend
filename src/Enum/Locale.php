<?php
declare(strict_types = 1);
/**
 * /src/Enum/Locale.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

// phpcs:ignoreFile

namespace App\Enum;

use App\Enum\Interfaces\StringEnumInterface;
use App\Enum\Traits\GetValues;

/**
 * Enum Locale
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Locale: string implements StringEnumInterface
{
    use GetValues;

    public const ENGLISH = 'en';
    public const FINNISH = 'fi';

    case EN = self::ENGLISH;
    case FI = self::FINNISH;

    public static function getDefault(): self
    {
        return self::EN;
    }
}
