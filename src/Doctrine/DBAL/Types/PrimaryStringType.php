<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/PrimaryStringType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Override;

/**
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class PrimaryStringType extends StringType
{
    protected static string $name = Types::PRIMARY_STRING;

    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 255;

        return $platform->getStringTypeDeclarationSQL($column);
    }
}
