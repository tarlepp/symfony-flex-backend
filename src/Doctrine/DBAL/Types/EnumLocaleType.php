<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLocaleType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Language;
use App\Enum\Locale;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use function gettype;
use function is_null;
use function is_string;

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

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     *
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * TODO: add test cases for this
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Locale
    {
        $enum = Locale::tryFrom($value);

        if (is_string($value) && !is_null($enum)) {
            return $enum;
        }

        throw ConversionException::conversionFailedFormat(
            gettype($value),
            $this->getName(),
            'One of: "' . implode('",', Language::getValues()) . '"',
        );
    }
}
