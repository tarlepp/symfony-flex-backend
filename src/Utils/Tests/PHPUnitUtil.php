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
     * @param mixed     $object The instantiated instance of your class
     * @param string    $name   The name of your private/protected method
     * @param array     $args   Method arguments
     *
     * @return mixed
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
     * @param mixed     $object The instantiated instance of your class
     * @param string    $name   The name of your private/protected method
     *
     * @return \ReflectionMethod The method you asked for
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
            case 'integer':
            case 'bigint':
                $output = 'integer';
                break;
            case 'time':
            case 'date':
            case 'datetime':
                $output = \DateTime::class;
                break;
            case 'text':
            case 'string':
            case 'EnumLogLogin':
                $output = 'string';
                break;
            case 'array':
                $output = 'array';
                break;
            case 'boolean':
                $output = 'boolean';
                break;
            default:
                $message = \sprintf(
                    "Currently type '%s' is not supported within type normalizer",
                    $type
                );

                throw new \LogicException($message);
                break;
        }

        return $output;
    }

    /**
     * Helper method to override any property value within given class.
     *
     * @param string    $property
     * @param mixed     $value
     * @param mixed     $object
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

            $type = 'CustomClass';
        }

        if (\strpos($type, '|') !== false) {
            $output = self::getValidValueForType(\explode('|', $type)[0], $meta);
        } else {
            switch ($type) {
                case 'CustomClass':
                    $output = new $class();
                    break;
                case 'integer':
                    $output = 666;
                    break;
                case \DateTime::class:
                    $output = new \DateTime();
                    break;
                case 'string':
                    $output = 'Some text here';
                    break;
                case 'array':
                    $output = ['some', 'array', 'here'];
                    break;
                case 'boolean':
                    $output = true;
                    break;
                default:
                    $message = \sprintf(
                        "Cannot create valid value for type '%s'.",
                        $type
                    );

                    throw new \LogicException($message);
                    break;
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
            $type = 'CustomClass';
        }

        if (\strpos($type, '|') !== false) {
            $output = self::getInvalidValueForType(\explode('|', $type)[0]);
        } else {
            switch ($type) {
                case \stdClass::class:
                    $output = new \DateTime();
                    break;
                case 'CustomClass':
                case 'integer':
                case \DateTime::class:
                case 'string':
                case 'array':
                case 'boolean':
                case 'enumLogLogin':
                    $output = new \stdClass();
                    break;
                default:
                    $message = \sprintf(
                        "Cannot create invalid value for type '%s'.",
                        $type
                    );

                    throw new \LogicException($message);
                    break;
            }
        }

        return $output;
    }
}
