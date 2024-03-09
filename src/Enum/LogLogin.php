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
 * LogLogin enum
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum LogLogin: string implements DatabaseEnumInterface
{
    use GetValues;

    case FAILURE = 'failure';
    case SUCCESS = 'success';
}
