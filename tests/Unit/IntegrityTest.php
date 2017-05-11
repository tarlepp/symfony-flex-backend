<?php
declare(strict_types=1);
/**
 * /tests/Unit/IntegrityTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class IntegrityTest
 *
 * @package App\Tests\Unit
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IntegrityTest extends KernelTestCase
{
    /**
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
     * @dataProvider dataProviderTestThatControllersHaveFunctionalTests
     *
     * @param string $controllerTestClass
     * @param string $controllerClass
     */
    public function testThatControllerHaveFunctionalTests(string $controllerTestClass, string $controllerClass): void
    {
        $message = \sprintf(
            'Controller \'%s\' doesn\'t have required test class \'%s\'.',
            $controllerClass,
            $controllerTestClass
        );

        static::assertTrue(\class_exists($controllerTestClass), $message);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatControllersHaveFunctionalTests(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getRootDir() . '/Controller/';
        $pattern = '/^.+Controller\.php$/i';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\Functional\\Controller\\';

        $iterator = function (string $file) use ($folder, $namespace, $namespaceTest) {
            $class = $namespace . \str_replace([$folder, '.php', \DIRECTORY_SEPARATOR], ['', '', '\\'], $file);
            $classTest = $namespaceTest . \str_replace([$folder, '.php', \DIRECTORY_SEPARATOR], ['', 'Test', '\\'], $file);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map($iterator, self::recursiveFileSearch($folder, $pattern));
    }
}
