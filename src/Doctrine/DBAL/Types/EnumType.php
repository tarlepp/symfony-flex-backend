<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Interfaces\DatabaseEnumInterface;
use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Override;
use function array_map;
use function gettype;
use function implode;
use function in_array;
use function is_string;

/**
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class EnumType extends Type
{
    protected static string $name;

    /**
     * @psalm-var class-string<DatabaseEnumInterface&BackedEnum>
     */
    protected static string $enum;

    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return static::$enum::getValues();
    }

    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $enumDefinition = implode(
            ', ',
            array_map(static fn (string $value): string => "'" . $value . "'", static::getValues()),
        );

        return 'ENUM(' . $enumDefinition . ')';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if (is_string($value) && in_array($value, static::$enum::getValues(), true)) {
            $value = static::$enum::from($value);
        }

        if (!in_array($value, static::$enum::cases(), true)) {
            $message = sprintf(
                "Invalid '%s' value '%s'",
                static::$name,
                is_string($value) ? $value : gettype($value),
            );

            throw new InvalidArgumentException($message);
        }

        return (string)parent::convertToDatabaseValue($value->value, $platform);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): DatabaseEnumInterface
    {
        $value = (string)parent::convertToPHPValue($value, $platform);
        $enum = static::$enum::tryFrom($value);

        if ($enum !== null) {
            return $enum;
        }

        throw ConversionException::conversionFailedFormat(
            gettype($value),
            static::$name,
            'One of: "' . implode('", "', static::getValues()) . '"',
        );
    }

    /**
     * Parent method is deprecated, so remove this after it has been removed.
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getName(): string
    {
        return '';
    }
}
