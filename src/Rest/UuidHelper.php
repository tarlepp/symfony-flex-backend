<?php
declare(strict_types = 1);
/**
 * /src/Rest/UuidHelper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

/**
 * Class UuidHelper
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UuidHelper
{
    /**
     * @return UuidFactory
     */
    public static function getFactory(): UuidFactory
    {
        static $cache = null;

        if ($cache === null) {
            /** @var UuidFactory $factory */
            $factory = clone Uuid::getFactory();
            $codec = new OrderedTimeCodec($factory->getUuidBuilder());
            $factory->setCodec($codec);

            $cache = $factory;
        }

        return $cache;
    }

    /**
     * Method to get proper doctrine parameter type for UuidBinaryOrderedTimeType values.
     *
     * @param string $value
     *
     * @return string|null
     */
    public static function getType(string $value): ?string
    {
        $factory = self::getFactory();
        $output = null;

        try {
            $factory->fromString($value);

            $output = UuidBinaryOrderedTimeType::NAME;
        } /** @noinspection BadExceptionsProcessingInspection */ catch (InvalidUuidStringException $exception) {
            // ok, so now we know that value isn't uuid
        }

        return $output;
    }

    /**
     * Method to get bytes value for specified UuidBinaryOrderedTimeType value.
     *
     * @param string $value
     *
     * @return string
     */
    public static function getBytes(string $value): string
    {
        return self::getFactory()->fromString($value)->getBytes();
    }
}
