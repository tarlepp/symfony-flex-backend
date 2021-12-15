<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLocaleType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Locale;

/**
 * Class EnumLocaleType
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLocaleType extends EnumType
{
    protected static string $name = Types::ENUM_LOCALE;

    /**
     * @var class-string
     */
    protected static string $enum = Locale::class;
}
