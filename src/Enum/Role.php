<?php
declare(strict_types = 1);
/**
 * /src/Enum/Role.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Enum;

/**
 * Enum Role
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Role: string
{
    case LOGGED = 'ROLE_LOGGED';
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case ROOT = 'ROLE_ROOT';
    case API = 'ROLE_API';

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
