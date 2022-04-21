<?php
declare(strict_types = 1);
/**
 * /src/Enum/Login.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

// phpcs:ignoreFile

namespace App\Enum;

use App\Enum\Interfaces\StringEnumInterface;
use App\Enum\Traits\GetValues;

/**
 * Enum Login
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Login: string implements StringEnumInterface
{
    use GetValues;

    public const FAIL = 'failure';
    public const OK = 'success';

    case FAILURE = self::FAIL;
    case SUCCESS = self::OK;
}
