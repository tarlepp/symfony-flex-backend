<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/PHPUnitUtil.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Tests;

use App\Entity\Role;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Type;
use Exception;
use LogicException;
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
use function strpos;
use function substr_count;

/**
 * Class PHPUnitUtil
 *
 * @package App\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
            $fileList[] = (string)$file[0];
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
     * @return mixed
     *
     * @throws ReflectionException
     */
    public static function callMethod($object, string $name, array $args)
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
    public static function getMethod($object, string $name): ReflectionMethod
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
     * @param object $object
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    public static function getProperty(string $property, $object)
    {
        $clazz = new ReflectionClass(get_class($object));

        $property = $clazz->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param Type|string|null $type
     */
    public static function getType($type): string
    {
        switch ($type) {
            case self::TYPE_INT:
            case self::TYPE_INTEGER:
            case 'bigint':
                $output = self::TYPE_INT;
                break;
            case 'time':
            case 'date':
            case 'datetime':
                $output = DateTime::class;
                break;
            case 'time_immutable':
            case 'date_immutable':
            case 'datetime_immutable':
                $output = DateTimeImmutable::class;
                break;
            case 'text':
            case self::TYPE_STRING:
            case 'EnumLanguage':
            case 'EnumLocale':
            case 'EnumLogLogin':
                $output = self::TYPE_STRING;
                break;
            case self::TYPE_ARRAY:
                $output = self::TYPE_ARRAY;
                break;
            case self::TYPE_BOOL:
            case self::TYPE_BOOLEAN:
                $output = self::TYPE_BOOL;
                break;
            default:
                $message = sprintf(
                    "Currently type '%s' is not supported within type normalizer",
                    (string)$type
                );

                throw new LogicException($message);
        }

        return $output;
    }

    /**
     * Helper method to override any property value within given class.
     *
     * @param mixed $value
     * @param object $object
     *
     * @throws ReflectionException
     */
    public static function setProperty(string $property, $value, $object): void
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
     * @return mixed
     *
     * @throws Throwable
     */
    public static function getValidValueForType(string $type, ?array $meta = null)
    {
        $cacheKey = $type . serialize($meta);

        if (!array_key_exists($cacheKey, self::$validValueCache)) {
            $meta ??= [];

            $class = stdClass::class;
            $params = [null];

            if (substr_count($type, '\\') > 1 && strpos($type, '|') === false) {
                /** @var class-string $class */
                $class = count($meta) ? $meta['targetEntity'] : $type;

                $type = self::TYPE_CUSTOM_CLASS;

                $cleanClass = $class[0] === '\\' ? ltrim($class, '\\') : $class;

                if ($cleanClass === Role::class) {
                    $params = ['Some Role'];
                }
            }

            if (strpos($type, '|') !== false) {
                $output = self::getValidValueForType(explode('|', $type)[0], $meta);
            } elseif (strpos($type, '[]') !== false) {
                /** @var array<mixed, object> $output */
                $output = self::getValidValueForType(self::TYPE_ARRAY, $meta);
            } else {
                switch ($type) {
                    case self::TYPE_CUSTOM_CLASS:
                        $output = new $class(...$params);
                        break;
                    case self::TYPE_INT:
                    case self::TYPE_INTEGER:
                        $output = 666;
                        break;
                    case DateTime::class:
                        $output = new DateTime();
                        break;
                    case DateTimeImmutable::class:
                        $output = new DateTimeImmutable();
                        break;
                    case self::TYPE_STRING:
                        $output = 'Some text here';
                        break;
                    case self::TYPE_ARRAY:
                        $output = ['some', self::TYPE_ARRAY, 'here'];
                        break;
                    case self::TYPE_BOOL:
                    case self::TYPE_BOOLEAN:
                        $output = true;
                        break;
                    default:
                        $message = sprintf(
                            "Cannot create valid value for type '%s'.",
                            $type
                        );

                        throw new LogicException($message);
                }
            }

            self::$validValueCache[$cacheKey] = $output;
        }

        return self::$validValueCache[$cacheKey];
    }

    /**
     * Helper method to get invalid value for specified type.
     *
     * @return stdClass|DateTime|string
     *
     * @throws Throwable
     */
    public static function getInvalidValueForType(string $type)
    {
        if ($type !== stdClass::class && substr_count($type, '\\') > 1) {
            $type = self::TYPE_CUSTOM_CLASS;
        }

        if (!array_key_exists($type, self::$invalidValueCache)) {
            if (strpos($type, '|') !== false) {
                $output = self::getInvalidValueForType(explode('|', $type)[0]);
            } elseif (strpos($type, '[]') !== false) {
                $output = self::getInvalidValueForType(self::TYPE_ARRAY);
            } else {
                switch ($type) {
                    case stdClass::class:
                    case DateTimeImmutable::class:
                        $output = new DateTime();
                        break;
                    case self::TYPE_CUSTOM_CLASS:
                    case self::TYPE_INT:
                    case self::TYPE_INTEGER:
                    case DateTime::class:
                    case self::TYPE_STRING:
                    case self::TYPE_ARRAY:
                    case self::TYPE_BOOL:
                    case self::TYPE_BOOLEAN:
                    case 'enumLanguage':
                    case 'enumLocale':
                    case 'enumLogLogin':
                        $output = new stdClass();
                        break;
                    default:
                        $message = sprintf(
                            "Cannot create invalid value for type '%s'.",
                            $type
                        );

                        throw new LogicException($message);
                }
            }

            self::$invalidValueCache[$type] = $output;
        }

        return self::$invalidValueCache[$type];
    }
}
