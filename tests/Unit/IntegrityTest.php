<?php
declare(strict_types=1);
/**
 * /tests/Unit/IntegrityTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\Repository;
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
     * @dataProvider dataProviderTestThatRepositoryHaveFunctionalTests
     *
     * @param string $repositoryTestClass
     * @param string $repositoryClass
     * @param array  $methods
     */
    public function testThatRepositoryHaveFunctionalTests(
        string $repositoryTestClass,
        string $repositoryClass,
        array $methods
    ): void
    {
        $message = \sprintf(
            'Repository \'%s\' doesn\'t have required test class \'%s\', repository has following methods that needs to be tested: \'%s\'.',
            $repositoryClass,
            $repositoryTestClass,
            \implode('\', \'', $methods)
        );

        static::assertTrue(\class_exists($repositoryTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRestRepositoryHaveIntegrationTests
     *
     * @param string $repositoryTestClass
     * @param string $repositoryClass
     */
    public function testThatRestRepositoryHaveIntegrationTests(
        string $repositoryTestClass,
        string $repositoryClass
    ): void
    {
        $message = \sprintf(
            'Repository \'%s\' doesn\'t have required test class \'%s\'.',
          $repositoryClass,
          $repositoryTestClass
        );

        static::assertTrue(\class_exists($repositoryTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatEntityHaveIntegrationTests
     *
     * @param string $entityTestClass
     * @param string $entityClass
     */
    public function testThatEntityHaveIntegrationTests(string $entityTestClass, string $entityClass): void
    {
        $message = \sprintf(
            'Entity \'%s\' doesn\'t have required test class \'%s\'.',
            $entityClass,
            $entityTestClass
        );

        static::assertTrue(\class_exists($entityTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatEventSubscriberHaveIntegrationTest
     *
     * @param string $eventSubscriberTestClass
     * @param string $eventSubscriberClass
     */
    public function testThatEventSubscriberHaveIntegrationTest(
        string $eventSubscriberTestClass,
        string $eventSubscriberClass
    ): void
    {
        $message = \sprintf(
            'EventSubscriber \'%s\' doesn\'t have required test class \'%s\'.',
            $eventSubscriberClass,
            $eventSubscriberTestClass
        );

        static::assertTrue(\class_exists($eventSubscriberTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatResourceHaveIntegrationTest
     *
     * @param string $resourceTestClass
     * @param string $resourceClass
     */
    public function testThatResourceHaveIntegrationTest(string $resourceTestClass, string $resourceClass): void
    {
        $message = \sprintf(
            'Resource \'%s\' doesn\'t have required test class \'%s\'.',
            $resourceClass,
            $resourceTestClass
        );

        static::assertTrue(\class_exists($resourceTestClass), $message);
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
            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map($iterator, self::recursiveFileSearch($folder, $pattern));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRepositoryHaveFunctionalTests(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getRootDir() . '/Repository/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Functional\\Repository\\';

        $repositoryMethods = [];

        $iterator = function (string $file) use ($folder, $namespace) {
            $repositoryClass = $namespace . \str_replace([$folder, '.php', \DIRECTORY_SEPARATOR], ['', '', '\\'], $file);

            return new \ReflectionClass($repositoryClass);
        };

        $filter = function (\ReflectionClass $reflectionClass) use (&$repositoryMethods) {
            $filter = function (\ReflectionMethod $method) use ($reflectionClass) {
                return $method->class === $reflectionClass->getName();
            };

            $methods = \array_filter($reflectionClass->getMethods(), $filter);

            $formatter = function (\ReflectionMethod $method) {
                return $method->getName();
            };

            $repositoryMethods[$reflectionClass->getName()] = \array_map($formatter, $methods);

            return !($reflectionClass->isAbstract() || $reflectionClass->isInterface() || empty($methods));
        };

        $formatter = function (\ReflectionClass $reflectionClass) use (&$repositoryMethods, $folder, $namespace, $namespaceTest) {
            $file = $reflectionClass->getFileName();

            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
                $repositoryMethods[$reflectionClass->getName()],
            ];
        };

        return \array_map(
            $formatter,
            \array_filter(
                \array_map(
                    $iterator,
                    self::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRestRepositoryHaveIntegrationTests(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getRootDir() . '/Repository/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';

        $iterator = function (string $file) use ($folder, $namespace) {
            $repositoryClass = $namespace . \str_replace([$folder, '.php', \DIRECTORY_SEPARATOR], ['', '', '\\'], $file);

            return new \ReflectionClass($repositoryClass);
        };

        $filter = function (\ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(Repository::class);
        };

        $formatter = function (\ReflectionClass $reflectionClass) use ($folder, $namespace, $namespaceTest) {
            $file = $reflectionClass->getFileName();

            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map(
            $formatter,
            \array_filter(
                \array_map(
                    $iterator,
                    self::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatEntityHaveIntegrationTests(): array
    {
        $folder = static::$kernel->getRootDir() . '/Entity/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Entity\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Entity\\';

        $iterator = function (string $file) use ($folder, $namespace) {
            $repositoryClass = $namespace . \str_replace([$folder, '.php', \DIRECTORY_SEPARATOR], ['', '', '\\'], $file);

            return new \ReflectionClass($repositoryClass);
        };

        $filter = function (\ReflectionClass $reflectionClass) {
            return !$reflectionClass->isInterface() && $reflectionClass->implementsInterface(EntityInterface::class);
        };

        $formatter = function (\ReflectionClass $reflectionClass) use ($folder, $namespace, $namespaceTest) {
            $file = $reflectionClass->getFileName();

            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map(
            $formatter,
            \array_filter(
                \array_map(
                    $iterator,
                    self::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatEventSubscriberHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getRootDir() . '/EventSubscriber/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\EventSubscriber\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventSubscriber\\';

        $iterator = function (string $file) use ($folder, $namespace, $namespaceTest) {
            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map($iterator, self::recursiveFileSearch($folder, $pattern));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatResourceHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getRootDir() . '/Resource/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Resource\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Resource\\';

        $iterator = function (string $file) use ($folder, $namespace, $namespaceTest) {
            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map($iterator, self::recursiveFileSearch($folder, $pattern));
    }
}
