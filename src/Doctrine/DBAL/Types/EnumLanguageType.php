<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLanguageType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Language;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use function gettype;
use function implode;
use function is_string;

/**
 * Class EnumLanguageType
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLanguageType extends EnumType
{
    protected static string $name = Types::ENUM_LANGUAGE;

    /**
     * @var class-string
     */
    protected static string $enum = Language::class;

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Language
    {
        $enum = Language::tryFrom($value);

        if (is_string($value) && $enum !== null) {
            return $enum;
        }

        throw ConversionException::conversionFailedFormat(
            gettype($value),
            $this->getName(),
            'One of: "' . implode('", "', Language::getValues()) . '"',
        );
    }
}
