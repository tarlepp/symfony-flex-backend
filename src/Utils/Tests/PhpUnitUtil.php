<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/PHPUnitUtil.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Utils\Tests;

use App\Entity\Role;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Type;
use Exception;
use LogicException;
use Ramsey\Uuid\UuidInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RegexIterator;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use function array_key_exists;
use function count;
use function explode;
use function get_class;
use function sprintf;
use function str_contains;
use function substr_count;

/**
 * Class PHPUnitUtil
 *
 * @package App\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class PhpUnitUtil
{
    public const TYPE_INT = 'int';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_BOOL = 'bool';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_CUSTOM_CLASS = 'CustomClass';

    /**
     * @var array<string, mixed>
     */
    private static array $validValueCache = [];

    /**
     * @var array<string, stdClass|DateTime|string>
     */
    private static array $invalidValueCache = [];

    /**
     * @codeCoverageIgnore
     *
     * @throws Exception
     */
    public static function loadFixtures(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:fixtures:load',
            '--no-interaction' => true,
            '--quiet' => true,
        ]);

        $input->setInteractive(false);

        $application->run($input, new ConsoleOutput(ConsoleOutput::VERBOSITY_QUIET));
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array<int, string>
     */
    public static function recursiveFileSearch(string $folder, string $pattern): array
    {
        $dir = new RecursiveDirectoryIterator($folder);
        $ite = new RecursiveIteratorIterator($dir);

        /** @var array<int, string> $files */
        $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
        $fileList = [];

        foreach ($files as $file) {
            $fileList[] = $file[0];
        }

        return $fileList;
    }

    /**
     * Method to call specified 'protected' or 'private' method on given class.
     *
     * @param object $object The instantiated instance of your class
     * @param string $name The name of your private/protected method
     * @param array<int, mixed> $args Method arguments
     *
     * @throws ReflectionException
     */
    public static function callMethod(object $object, string $name, array $args): mixed
    {
        return self::getMethod($object, $name)->invokeArgs($object, $args);
    }

    /**
     * Get a private or protected method for testing/documentation purposes.
     * How to use for MyClass->foo():
     *      $cls = new MyClass();
     *      $foo = PHPUnitUtil::getPrivateMethod($cls, 'foo');
     *      $foo->invoke($cls, $...);
     *
     * @param object $object The instantiated instance of your class
     * @param string $name The name of your private/protected method
     *
     * @return ReflectionMethod The method you asked for
     *
     * @throws ReflectionException
     */
    public static function getMethod(object $object, string $name): ReflectionMethod
    {
        // Get reflection and make specified method accessible
        $class = new ReflectionClass($object);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Helper method to get any property value from given class.
     *
     * @throws ReflectionException
     */
    public static function getProperty(string $property, object $object): mixed
    {
        $clazz = new ReflectionClass(get_class($object));

        $property = $clazz->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public static function getType(Type | string | null $type): string
    {
        $exception = new LogicException(
            sprintf("Currently type '%s' is not supported within type normalizer", (string)$type),
        );

        return match ($type) {
            'time', 'date', 'datetime' => DateTime::class,
            'time_immutable', 'date_immutable', 'datetime_immutable' => DateTimeImmutable::class,
            self::TYPE_INT, self::TYPE_INTEGER, 'bigint' => self::TYPE_INT,
            self::TYPE_STRING, 'text', 'EnumLanguage', 'EnumLocale', 'EnumLogLogin' => self::TYPE_STRING,
            self::TYPE_ARRAY => self::TYPE_ARRAY,
            self::TYPE_BOOL, self::TYPE_BOOLEAN => self::TYPE_BOOL,
            default => throw $exception,
        };
    }

    /**
     * Helper method to override any property value within given class.
     *
     * @param UuidInterface|array<array-key, string>|null $value
     *
     * @throws ReflectionException
     */
    public static function setProperty(string $property, UuidInterface | array | null $value, object $object): void
    {
        $clazz = new ReflectionClass(get_class($object));

        $property = $clazz->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Helper method to get valid value for specified type.
     *
     * @param array<string, string>|null $meta
     *
     * @throws Throwable
     */
    public static function getValidValueForType(string $type, ?array $meta = null): mixed
    {
        $cacheKey = $type . serialize($meta);

        if (!array_key_exists($cacheKey, self::$validValueCache)) {
            self::$validValueCache[$cacheKey] = self::getValidValue($meta, $type);
        }

        return self::$validValueCache[$cacheKey];
    }

    /**
     * Helper method to get invalid value for specified type.
     *
     * @throws Throwable
     */
    public static function getInvalidValueForType(string $type): DateTime | stdClass | string
    {
        if ($type !== stdClass::class && substr_count($type, '\\') > 1) {
            $type = self::TYPE_CUSTOM_CLASS;
        }

        if (!array_key_exists($type, self::$invalidValueCache)) {
            if (str_contains($type, '|')) {
                $output = self::getInvalidValueForType(explode('|', $type)[0]);
            } elseif (str_contains($type, '[]')) {
                $output = self::getInvalidValueForType(self::TYPE_ARRAY);
            } else {
                $output = match ($type) {
                    stdClass::class, DateTimeImmutable::class
                        => new DateTime(),
                    self::TYPE_CUSTOM_CLASS, self::TYPE_INT, self::TYPE_INTEGER, self::TYPE_STRING, self::TYPE_ARRAY,
                    self::TYPE_BOOL, self::TYPE_BOOLEAN, DateTime::class, 'enumLanguage', 'enumLocale', 'enumLogLogin'
                        => new stdClass(),
                    default
                        => throw new LogicException(sprintf("Cannot create invalid value for type '%s'.", $type)),
                };
            }

            self::$invalidValueCache[$type] = $output;
        }

        return self::$invalidValueCache[$type];
    }

    /**
     * @param array<string, string>|null $meta
     *
     * @throws Throwable
     */
    private static function getValidValue(
        ?array $meta,
        string $type
    ): mixed {
        $meta ??= [];

        $class = stdClass::class;
        $params = [null];

        if (substr_count($type, '\\') > 1 && !str_contains($type, '|')) {
            /** @var class-string $class */
            $class = count($meta) ? $meta['targetEntity'] : $type;

            $type = self::TYPE_CUSTOM_CLASS;

            $cleanClass = $class[0] === '\\' ? ltrim($class, '\\') : $class;

            if ($cleanClass === Role::class) {
                $params = ['Some Role'];
            }
        }

        $output = match ($type) {
            self::TYPE_CUSTOM_CLASS => new $class(...$params),
            self::TYPE_INT, self::TYPE_INTEGER => 666,
            self::TYPE_STRING => 'Some text here',
            self::TYPE_ARRAY => ['some', self::TYPE_ARRAY, 'here'],
            self::TYPE_BOOL, self::TYPE_BOOLEAN => true,
            DateTime::class => new DateTime(),
            DateTimeImmutable::class => new DateTimeImmutable(),
            default => null,
        };

        if (str_contains($type, '|')) {
            $output = self::getValidValueForType(explode('|', $type)[0], $meta);
        } elseif (str_contains($type, '[]')) {
            /** @var array<mixed, object> $output */
            $output = self::getValidValueForType(self::TYPE_ARRAY, $meta);
        }

        return $output ?? throw new LogicException(sprintf("Cannot create valid value for type '%s'.", $type));
    }
}
