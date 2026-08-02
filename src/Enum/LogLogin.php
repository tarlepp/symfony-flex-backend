<?php
declare(strict_types = 1);

/**
 * /src/Enum/Locale.php
 */

namespace App\Enum;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Traits\GetValues;

/**
 * LogLogin enum
 */
enum LogLogin: string implements DatabaseEnumInterface
{
    use GetValues;

    case FAILURE = 'failure';
    case SUCCESS = 'success';
}
