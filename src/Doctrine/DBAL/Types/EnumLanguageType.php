<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLanguageType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Doctrine\DBAL\Types;

/**
 * Class EnumLanguageType
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EnumLanguageType extends EnumType
{
    public const LANGUAGE_EN = 'en';
    public const LANGUAGE_FI = 'fi';

    protected static string $name = 'EnumLanguage';

    /**
     * @var array<int, string>
     */
    protected static array $values = [
        self::LANGUAGE_EN,
        self::LANGUAGE_FI,
    ];
}
