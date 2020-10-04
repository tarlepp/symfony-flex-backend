<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use function array_map;
use function implode;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Class EnumType
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class EnumType extends Type
{
    protected static string $name;

    /**
     * @var array<int, string>
     */
    protected static array $values = [];

    public static function getValues(): array
    {
        return static::$values;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $iterator = static fn (string $value): string => "'" . $value . "'";

        return 'ENUM(' . implode(', ', array_map($iterator, self::getValues())) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        $value = (string)parent::convertToDatabaseValue(is_string($value) ? $value : '', $platform);

        if (!in_array($value, static::$values, true)) {
            $message = sprintf(
                "Invalid '%s' value",
                $this->getName()
            );

            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    public function getName(): string
    {
        return static::$name;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        parent::requiresSQLCommentHint($platform);

        return true;
    }
}
