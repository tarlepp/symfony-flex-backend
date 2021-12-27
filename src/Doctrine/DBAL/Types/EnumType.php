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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use function array_map;
use function gettype;
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
         * @var class-string<Language>|class-string<Locale>|class-string<Login> $enum
         */
        $enum = static::$enum;

        return $enum::getValues();
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $enumDefinition = implode(
            ', ',
            array_map(static fn (string $value): string => "'" . $value . "'", static::getValues()),
        );

        return 'ENUM(' . $enumDefinition . ')';
    }

    /**
     * {@inheritdoc}
     *
     * TODO: add test cases for this method for each enum type
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        /**
         * @var class-string<Language>|class-string<Locale>|class-string<Login> $enum
         */
        $enum = static::$enum;

        if (!in_array($value, $enum::cases(), true)) {
            $message = sprintf(
                "Invalid '%s' value '%s'",
                $this->getName(),
                is_string($value) ? $value : gettype($value),
            );

            throw new InvalidArgumentException($message);
        }

        return parent::convertToDatabaseValue($value->value, $platform);
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
