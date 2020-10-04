<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLocaleType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Doctrine\DBAL\Types;

/**
 * Class EnumLocaleType
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EnumLocaleType extends EnumType
{
    public const LOCALE_EN = 'en';
    public const LOCALE_FI = 'fi';

    protected static string $name = 'EnumLocale';

    /**
     * @var array<int, string>
     */
    protected static array $values = [
        self::LOCALE_EN,
        self::LOCALE_FI,
    ];
}
