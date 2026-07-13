<?php
declare(strict_types = 1);

/**
 * /src/Doctrine/DBAL/Types/EnumLanguageType.php
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Interfaces\DatabaseEnumInterface;
use App\Enum\Language;
use BackedEnum;

class EnumLanguageType extends EnumType
{
    protected static string $name = Types::ENUM_LANGUAGE;

    /**
     * @psalm-var class-string<DatabaseEnumInterface&BackedEnum>
     */
    protected static string $enum = Language::class;
}
