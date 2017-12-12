<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Class EnumType
 *
 * @package App\Doctrine\DBAL\Types
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class EnumType extends Type
{
    /**
     * @var string
     */
    protected static $name;

    /**
     * @var array
     */
    protected static $values = array();

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @SuppressWarnings("unused")
     *
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $iterator = function (string $value): string {
            return "'" . $value . "'";
        };

        return 'ENUM(' . \implode(', ', \array_map($iterator, static::$values)) . ')';
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $value = parent::convertToDatabaseValue($value, $platform);

        if (!\in_array($value, static::$values, true)) {
            $message = \sprintf(
                "Invalid '%s' value",
                $this->getName()
            );

            throw new \InvalidArgumentException($message);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::$name;
    }

    /**
     * If this Doctrine Type maps to an already mapped database type, reverse schema engineering can't take them apart.
     * You need to mark one of those types as commented, which will have Doctrine use an SQL comment to type hint the
     * actual Doctrine Type.
     *
     * @param AbstractPlatform $platform
     *
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        parent::requiresSQLCommentHint($platform);

        return true;
    }
}
