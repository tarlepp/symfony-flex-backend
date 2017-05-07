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
}
