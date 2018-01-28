<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/PHPUnitUtil.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class PHPUnitUtil
 *
 * @package App\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class PHPUnitUtil
{
    private const TYPE_INTEGER = 'integer';
    private const TYPE_STRING = 'string';
    private const TYPE_ARRAY = 'array';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_CUSTOM_CLASS = 'CustomClass';

    /**
     * @codeCoverageIgnore
     *
     * @param KernelInterface $kernel
     *
     * @throws \Exception
     */
    public static function loadFixtures(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command'           => 'doctrine:fixtures:load',
            '--no-interaction'  => true,
            '--quiet'           => true,
        ]);

        $input->setInteractive(false);

        $application->run($input, new ConsoleOutput(ConsoleOutput::VERBOSITY_QUIET));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $folder
     * @param string $pattern
     *
     * @return array
     */
    public static function recursiveFileSearch(string $folder, string $pattern): array
    {
        $dir = new \RecursiveDirectoryIterator($folder);
        $ite = new \RecursiveIteratorIterator($dir);

        $files = new \RegexIterator($ite, $pattern, \RegexIterator::GET_MATCH);
        $fileList = array();

        foreach ($files as $file) {
            $fileList[] = $file[0];
        }

        return $fileList;
    }

    /**
     * Method to call specified 'protected' or 'private' method on given class.
     *
     * @param mixed  $object The instantiated instance of your class
     * @param string $name   The name of your private/protected method
     * @param array  $args   Method arguments
     *
     * @return mixed
     *
     * @throws \ReflectionException
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
     * @param mixed  $object The instantiated instance of your class
     * @param string $name   The name of your private/protected method
     *
     * @return \ReflectionMethod The method you asked for
     * @throws \ReflectionException
     */
    public static function getMethod($object, string $name): \ReflectionMethod
    {
        // Get reflection and make specified method accessible
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Helper method to get any property value from given class.
     *
     * @param string $property
     * @param mixed  $object
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    public static function getProperty(string $property, $object)
    {
        $clazz = new \ReflectionClass(\get_class($object));

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $property = $clazz->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param \Doctrine\DBAL\Types\Type|string|null $type
     *
     * @return string
     */
    public static function getType($type): string
    {
        switch ($type) {
            case self::TYPE_INTEGER:
            case 'bigint':
                $output = self::TYPE_INTEGER;
                break;
            case 'time':
            case 'date':
            case 'datetime':
                $output = \DateTime::class;
                break;
            case 'text':
            case self::TYPE_STRING:
            case 'EnumLogLogin':
                $output = self::TYPE_STRING;
                break;
            case self::TYPE_ARRAY:
                $output = self::TYPE_ARRAY;
                break;
            case self::TYPE_BOOLEAN:
                $output = self::TYPE_BOOLEAN;
                break;
            default:
                $message = \sprintf(
                    "Currently type '%s' is not supported within type normalizer",
                    $type
                );

                throw new \LogicException($message);
        }

        return $output;
    }

    /**
     * Helper method to override any property value within given class.
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $object
     *
     * @throws \ReflectionException
     */
    public static function setProperty(string $property, $value, $object): void
    {
        $clazz = new \ReflectionClass(\get_class($object));

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $property = $clazz->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Helper method to get valid value for specified type.
     *
     * @param string     $type
     * @param array|null $meta
     *
     * @return mixed
     */
    public static function getValidValueForType(string $type, array $meta = null)
    {
        $meta = $meta ?? [];

        $class = \stdClass::class;

        if (\substr_count($type, '\\') > 1) {
            $class = \count($meta) ? $meta['targetEntity'] : $type;

            $type = self::TYPE_CUSTOM_CLASS;
        }

        if (\strpos($type, '|') !== false) {
            $output = self::getValidValueForType(\explode('|', $type)[0], $meta);
        } else {
            switch ($type) {
                case self::TYPE_CUSTOM_CLASS:
                    $output = new $class();
                    break;
                case self::TYPE_INTEGER:
                    $output = 666;
                    break;
                case \DateTime::class:
                    $output = new \DateTime();
                    break;
                case self::TYPE_STRING:
                    $output = 'Some text here';
                    break;
                case self::TYPE_ARRAY:
                    $output = ['some', self::TYPE_ARRAY, 'here'];
                    break;
                case self::TYPE_BOOLEAN:
                    $output = true;
                    break;
                default:
                    $message = \sprintf(
                        "Cannot create valid value for type '%s'.",
                        $type
                    );

                    throw new \LogicException($message);
            }
        }

        return $output;
    }

    /**
     * Helper method to get invalid value for specified type.
     *
     * @param string $type
     *
     * @return mixed
     */
    public static function getInvalidValueForType(string $type)
    {
        if ($type !== \stdClass::class && \substr_count($type, '\\') > 1) {
            $type = self::TYPE_CUSTOM_CLASS;
        }

        if (\strpos($type, '|') !== false) {
            $output = self::getInvalidValueForType(\explode('|', $type)[0]);
        } else {
            switch ($type) {
                case \stdClass::class:
                    $output = new \DateTime();
                    break;
                case self::TYPE_CUSTOM_CLASS:
                case self::TYPE_INTEGER:
                case \DateTime::class:
                case self::TYPE_STRING:
                case self::TYPE_ARRAY:
                case self::TYPE_BOOLEAN:
                case 'enumLogLogin':
                    $output = new \stdClass();
                    break;
                default:
                    $message = \sprintf(
                        "Cannot create invalid value for type '%s'.",
                        $type
                    );

                    throw new \LogicException($message);
            }
        }

        return $output;
    }
}
