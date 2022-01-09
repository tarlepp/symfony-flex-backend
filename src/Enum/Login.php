<?php
declare(strict_types = 1);
/**
 * /src/Enum/Login.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum;

use App\Enum\Interfaces\EnumInterface;
use function array_map;

/**
 * Enum Login
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Login: string implements EnumInterface
{
    case FAILURE = 'failure';
    case SUCCESS = 'success';

    public static function getValues(): array
    {
        return array_map(static fn (self $enum): string => $enum->value, self::cases());
    }
}
