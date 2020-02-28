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
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * Class UuidHelper
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UuidHelper
{
    private static ?UuidFactory $cache = null;

    /**
     * Getter method for UUID factory.
     *
     * @return UuidFactory
     */
    public static function getFactory(): UuidFactory
    {
        return self::$cache ??= self::initCache();
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
        } catch (InvalidUuidStringException $exception) {
            // ok, so now we know that value isn't uuid
            (static fn (Throwable $exception): Throwable => $exception)($exception);
        }

        return $output;
    }

    /**
     * Creates a UUID from the string standard representation
     *
     * @param string $value
     *
     * @return UuidInterface
     */
    public static function fromString(string $value): UuidInterface
    {
        return self::getFactory()->fromString($value);
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
        return self::fromString($value)->getBytes();
    }

    /**
     * Method to init UUID factory cache.
     *
     * @codeCoverageIgnore
     *
     * @return UuidFactory
     */
    private static function initCache(): UuidFactory
    {
        /** @var UuidFactory $factory */
        $factory = clone Uuid::getFactory();
        $codec = new OrderedTimeCodec($factory->getUuidBuilder());
        $factory->setCodec($codec);

        return $factory;
    }
}
