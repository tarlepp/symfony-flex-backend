<?php
declare(strict_types = 1);
/**
 * /src/Enum/Language.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum;

use App\Enum\Interfaces\EnumInterface;
use function array_map;

/**
 * Enum Language
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Language: string implements EnumInterface
{
    case EN = 'en';
    case FI = 'fi';

    public static function getDefault(): self
    {
        return self::EN;
    }

    public static function getValues(): array
    {
        return array_map(static fn (self $enum): string => $enum->value, self::cases());
    }
}
