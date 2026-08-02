<?php
declare(strict_types = 1);

/**
 * /src/Doctrine/DBAL/Types/EnumLocaleType.php
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Locale;
use BackedEnum;

class EnumLocaleType extends EnumType
{
    protected static string $name = Types::ENUM_LOCALE;

    /**
     * @psalm-var class-string<DatabaseEnumInterface&BackedEnum>
     */
    protected static string $enum = Locale::class;
}
