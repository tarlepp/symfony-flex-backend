<?php
declare(strict_types=1);
/**
 * /tests/Helpers/PHPUnitUtil.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Helpers;

/**
 * Class PHPUnitUtil
 *
 * @package App\Tests\Helpers
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class PHPUnitUtil
{
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

        switch ($type) {
            case 'CustomClass':
                $value = new $class();
                break;
            case 'integer':
                $value = 666;
                break;
            case \DateTime::class:
                $value = new \DateTime();
                break;
            case 'string':
                $value = 'Some text here';
                break;
            case 'array':
                $value = ['some', 'array', 'here'];
                break;
            case 'boolean':
                $value = true;
                break;
            default:
                $message = \sprintf(
                    "Cannot create valid value for type '%s'.",
                    $type
                );

                throw new \LogicException($message);
                break;
        }

        return $value;
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

        switch ($type) {
            case \stdClass::class:
                $value = new \DateTime();
                break;
            case 'integer':
            case \DateTime::class:
            case 'string':
            case 'array':
            case 'boolean':
                $value = new \stdClass();
                break;
            default:
                $message = \sprintf(
                    "Cannot create invalid value for type '%s'.",
                    $type
                );

                throw new \LogicException($message);
                break;
        }

        return $value;
    }
}
