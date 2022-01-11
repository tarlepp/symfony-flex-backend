<?php
declare(strict_types = 1);
/**
 * /src/Enum/Login.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

// phpcs:ignoreFile

namespace App\Enum;

use App\Enum\Interfaces\EnumInterface;
use App\Enum\Traits\GetValues;

/**
 * Enum Login
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Login: string implements EnumInterface
{
    use GetValues;

    case FAILURE = 'failure';
    case SUCCESS = 'success';
}
