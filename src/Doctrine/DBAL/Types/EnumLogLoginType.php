<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLogLoginType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use App\Enum\Login;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use function gettype;
use function implode;
use function is_string;

/**
 * Class EnumLogLoginType
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLogLoginType extends EnumType
{
    protected static string $name = Types::ENUM_LOG_LOGIN;

    /**
     * @var class-string
     */
    protected static string $enum = Login::class;

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Login
    {
        $enum = Login::tryFrom($value);

        if (is_string($value) && $enum !== null) {
            return $enum;
        }

        throw ConversionException::conversionFailedFormat(
            gettype($value),
            $this->getName(),
            'One of: "' . implode('", "', Login::getValues()) . '"',
        );
    }
}
