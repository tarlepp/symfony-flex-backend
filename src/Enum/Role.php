<?php
declare(strict_types = 1);

/**
 * /src/Enum/Role.php
 */

namespace App\Enum;

use App\Enum\Interfaces\LabeledEnumInterface;
use Override;

/**
 * Enum Role
 */
enum Role: string implements LabeledEnumInterface
{
    case LOGGED = 'ROLE_LOGGED';
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case ROOT = 'ROLE_ROOT';
    case API = 'ROLE_API';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::LOGGED => 'Logged in users',
            self::USER => 'Normal users',
            self::ADMIN => 'Admin users',
            self::ROOT => 'Root users',
            self::API => 'API users',
        };
    }
}
