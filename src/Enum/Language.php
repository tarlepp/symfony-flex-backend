<?php
declare(strict_types = 1);
/**
 * /src/Enum/Language.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Traits\GetValues;

/**
 * Language Role
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Language: string implements DatabaseEnumInterface
{
    use GetValues;

    case EN = 'en';
    case FI = 'fi';

    public static function getDefault(): self
    {
        return self::EN;
    }
}
