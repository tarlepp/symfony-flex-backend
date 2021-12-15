<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Language;
use App\Enum\Locale;
use App\Enum\Login;
use BackedEnum;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class EnumType extends Type
{
    protected static string $name;

    /**
     * @var class-string
     */
    protected static string $enum;

    /**
     * @var array<int, string>
     */
    protected static array $values = [];

    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        /**
         * @var class-string<Language>|class-string<Locale>|class-string<Login> $foo
         */
        $foo = static::$enum;

        return array_map(static fn (BackedEnum $enum): string => $enum->value, $foo::cases());
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $enumDefinition = implode(
            ', ',
            array_map(static fn (string $value): string => "'" . $value . "'", self::getValues()),
        );

        return 'ENUM(' . $enumDefinition . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        $value = (string)parent::convertToDatabaseValue(is_string($value) ? $value : '', $platform);

        if (!in_array($value, static::getValues(), true)) {
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
