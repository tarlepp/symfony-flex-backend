<?php
declare(strict_types = 1);
/**
 * /src/Enum/Role.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

// phpcs:ignoreFile

namespace App\Enum;

use App\Enum\Interfaces\StringEnumInterface;
use App\Enum\Traits\GetValues;
use InvalidArgumentException;
use function is_string;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;

/**
 * Enum Role
 *
 * @package App\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
enum Role: string implements StringEnumInterface
{
    use GetValues;

    public const LOGGED = 'ROLE_LOGGED';
    public const USER = 'ROLE_USER';
    public const ADMIN = 'ROLE_ADMIN';
    public const ROOT = 'ROLE_ROOT';
    public const API = 'ROLE_API';

    case ROLE_LOGGED = self::LOGGED;
    case ROLE_USER = self::USER;
    case ROLE_ADMIN = self::ADMIN;
    case ROLE_ROOT = self::ROOT;
    case ROLE_API = self::API;

    public static function getLabelForRole(self|string $role): string
    {
        return match (is_string($role) ? self::tryFrom($role) : $role) {
            self::ROLE_LOGGED => 'Logged in users',
            self::ROLE_USER => 'Normal users',
            self::ROLE_ADMIN => 'Admin users',
            self::ROLE_ROOT => 'Root users',
            self::ROLE_API => 'API users',
            default => throw new InvalidArgumentException('Unknown - ' . (is_string($role) ? $role : $role->value)),
        };
    }

    public static function getShortForRole(self|string $role): string
    {
        $roleEnum = (is_string($role) ? self::tryFrom($role) : $role);

        if ($roleEnum === null) {
            throw new InvalidArgumentException('Unknown - ' . (is_string($role) ? $role : $role->value));
        }

        $offset = mb_strpos($roleEnum->value, '_');
        $offset = $offset !== false ? $offset + 1 : 0;

        return mb_strtolower(mb_substr($roleEnum->value, $offset));
    }

    public function getLabel(): string
    {
        return self::getLabelForRole($this);
    }

    public function getShort(): string
    {
        return self::getShortForRole($this);
    }
}
