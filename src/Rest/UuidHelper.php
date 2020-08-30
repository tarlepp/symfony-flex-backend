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
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * Class UuidHelper
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UuidHelper
{
    private static ?UuidFactory $cache = null;

    /**
     * Getter method for UUID factory.
     */
    public static function getFactory(): UuidFactory
    {
        return self::$cache ??= self::initCache();
    }

    /**
     * Method to get proper doctrine parameter type for
     * UuidBinaryOrderedTimeType values.
     */
    public static function getType(string $value): ?string
    {
        $factory = self::getFactory();
        $output = null;

        try {
            $fields = $factory->fromString($value)->getFields();

            if ($fields instanceof FieldsInterface) {
                $output = $fields->getVersion() === 1 ? UuidBinaryOrderedTimeType::NAME : UuidBinaryType::NAME;
            }
        } catch (InvalidUuidStringException $exception) {
            // ok, so now we know that value isn't uuid
            (static fn (Throwable $exception): Throwable => $exception)($exception);
        }

        return $output;
    }

    /**
     * Creates a UUID from the string standard representation
     */
    public static function fromString(string $value): UuidInterface
    {
        return self::getFactory()->fromString($value);
    }

    /**
     * Method to get bytes value for specified UuidBinaryOrderedTimeType value.
     */
    public static function getBytes(string $value): string
    {
        return self::fromString($value)->getBytes();
    }

    /**
     * Method to init UUID factory cache.
     *
     * @codeCoverageIgnore
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
