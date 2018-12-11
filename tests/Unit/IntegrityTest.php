<?php
declare(strict_types=1);
/**
 * /tests/Unit/IntegrityTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit;

use App\Entity\EntityInterface;
use App\Rest\ControllerInterface;
use App\Rest\RepositoryInterface;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * Class IntegrityTest
 *
 * @package App\Tests\Unit
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IntegrityTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatControllerHasE2ETests
     *
     * @param string $controllerTestClass
     * @param string $controllerClass
     */
    public function testThatControllerHasE2ETests(string $controllerTestClass, string $controllerClass): void
    {
        $message = \sprintf(
            'Controller \'%s\' doesn\'t have required test class \'%s\'.',
            $controllerClass,
            $controllerTestClass
        );

        static::assertTrue(\class_exists($controllerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRestControllerHaveIntegrationTests
     *
     * @param string $controllerTestClass
     * @param string $controllerClass
     */
    public function testThatRestControllerHaveIntegrationTests(
        string $controllerTestClass,
        string $controllerClass
    ): void {
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
    ): void {
        $format = <<<FORMAT
Repository '%s' doesn't have required test class '%s', repository has following methods that needs to be tested: '%s'.
FORMAT;

        $message = \sprintf(
            $format,
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
    ): void {
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
    ): void {
        $message = \sprintf(
            'EventSubscriber \'%s\' doesn\'t have required test class \'%s\'.',
            $eventSubscriberClass,
            $eventSubscriberTestClass
        );

        static::assertTrue(\class_exists($eventSubscriberTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatEventListenerHaveIntegrationTest
     *
     * @param string $eventListenerTestClass
     * @param string $eventListenerClass
     */
    public function testThatEventListenerHaveIntegrationTest(
        string $eventListenerTestClass,
        string $eventListenerClass
    ): void {
        $message = \sprintf(
            'EventListener \'%s\' doesn\'t have required test class \'%s\'.',
            $eventListenerClass,
            $eventListenerTestClass
        );

        static::assertTrue(\class_exists($eventListenerTestClass), $message);
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
     * @dataProvider dataProviderTestThatDtoHaveIntegrationTest
     *
     * @param string $dtoTestClass
     * @param string $dtoClass
     */
    public function testThatDtoHaveIntegrationTest(string $dtoTestClass, string $dtoClass): void
    {
        $message = \sprintf(
            'REST DTO \'%s\' doesn\'t have required test class \'%s\'.',
            $dtoClass,
            $dtoTestClass
        );

        static::assertTrue(\class_exists($dtoTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatFormTypeHaveIntegrationTest
     *
     * @param string $formTypeTestClass
     * @param string $formTypeClass
     */
    public function testThatFormTypeHaveIntegrationTest(string $formTypeTestClass, string $formTypeClass): void
    {
        $message = \sprintf(
            'Form type \'%s\' doesn\'t have required test class \'%s\'.',
            $formTypeClass,
            $formTypeTestClass
        );

        static::assertTrue(\class_exists($formTypeTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatDataTransformerHaveIntegrationTest
     *
     * @param string $dataTransformerTestClass
     * @param string $dataTransformerClass
     */
    public function testThatDataTransformerHaveIntegrationTest(
        string $dataTransformerTestClass,
        string $dataTransformerClass
    ): void {
        $message = \sprintf(
            'DataTransformer \'%s\' doesn\'t have required test class \'%s\'.',
            $dataTransformerClass,
            $dataTransformerTestClass
        );

        static::assertTrue(\class_exists($dataTransformerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatValidatorConstraintsHaveIntegrationTest
     *
     * @param string $validatorTestClass
     * @param string $validatorClass
     */
    public function testThatValidatorConstraintsHaveIntegrationTest(
        string $validatorTestClass,
        string $validatorClass
    ): void {
        $message = \sprintf(
            'Validator \'%s\' doesn\'t have required test class \'%s\'.',
            $validatorClass,
            $validatorTestClass
        );

        static::assertTrue(\class_exists($validatorTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatCustomDBALTypeHaveIntegrationTest
     *
     * @param string $dbalTypeTestClass
     * @param string $dbalTypeClass
     */
    public function testThatCustomDBALTypeHaveIntegrationTest(
        string $dbalTypeTestClass,
        string $dbalTypeClass
    ): void {
        $message = \sprintf(
            'DBAL type \'%s\' doesn\'t have required test class \'%s\'.',
            $dbalTypeClass,
            $dbalTypeTestClass
        );

        static::assertTrue(\class_exists($dbalTypeTestClass), $message);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatControllerHasE2ETests(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Controller/';
        $pattern = '/^.+Controller\.php$/i';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\E2E\\Controller\\';

        $iterator = function (string $file) use ($folder, $namespace, $namespaceTest) {
            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map($iterator, PhpUnitUtil::recursiveFileSearch($folder, $pattern));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRepositoryHaveFunctionalTests(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Functional\\Repository\\';

        $repositoryMethods = [];

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) use (&$repositoryMethods) {
            $filter = function (\ReflectionMethod $method) use ($reflectionClass) {
                return $method->class === $reflectionClass->getName();
            };

            $methods = \array_filter($reflectionClass->getMethods(), $filter);

            $formatter = function (\ReflectionMethod $method) {
                return $method->getName();
            };

            $repositoryMethods[$reflectionClass->getName()] = \array_map($formatter, $methods);

            return !(
                $reflectionClass->isAbstract() ||
                $reflectionClass->isInterface() ||
                $reflectionClass->isTrait() ||
                empty($methods)
            );
        };

        $formatter = function (\ReflectionClass $reflectionClass) use (
            &$repositoryMethods,
            $folder,
            $namespace,
            $namespaceTest
        ) {
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
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

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(RepositoryInterface::class);
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
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
        $folder = static::$kernel->getProjectDir() . '/src/Entity/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Entity\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Entity\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
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

        $folder = static::$kernel->getProjectDir() . '/src/EventSubscriber/';
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

        return \array_map($iterator, PhpUnitUtil::recursiveFileSearch($folder, $pattern));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatEventListenerHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/EventListener/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\EventListener\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventListener\\';

        $iterator = function (string $file) use ($folder, $namespace, $namespaceTest) {
            $base = \str_replace([$folder, \DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . \str_replace('.php', '', $base);
            $classTest = $namespaceTest . \str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };

        return \array_map($iterator, PhpUnitUtil::recursiveFileSearch($folder, $pattern));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatResourceHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Resource/';
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

        return \array_map($iterator, PhpUnitUtil::recursiveFileSearch($folder, $pattern));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatDtoHaveIntegrationTest(): array
    {
        $folder = static::$kernel->getProjectDir() . '/src/DTO/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\DTO\\';
        $namespaceTest = '\\App\\Tests\\Integration\\DTO\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return !$reflectionClass->isInterface() && !$reflectionClass->isAbstract();
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatFormTypeHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Form/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract() && $reflectionClass->implementsInterface(FormTypeInterface::class);
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatDataTransformerHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Form/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(DataTransformerInterface::class);
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRestControllerHaveIntegrationTests(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Controller/';
        $pattern = '/^.+Controller\.php$/i';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Controller\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(ControllerInterface::class);
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatValidatorConstraintsHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Validator/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Validator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Validator\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(ConstraintValidatorInterface::class);
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCustomDBALTypeHaveIntegrationTest(): array
    {
        self::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Doctrine/DBAL/Types/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Doctrine\\DBAL\\Types\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Doctrine\\DBAL\\Types\\';

        $iterator = $this->getReflectionClass($folder, $namespace);

        $filter = function (\ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(Type::class);
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
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    /**
     * @param string $folder
     * @param string $namespace
     *
     * @return \Closure
     */
    private function getReflectionClass(string $folder, string $namespace): \Closure
    {
        $iterator = function (string $file) use ($folder, $namespace) {
            $class = $namespace . \str_replace([$folder, '.php', \DIRECTORY_SEPARATOR], ['', '', '\\'], $file);

            return new \ReflectionClass($class);
        };

        return $iterator;
    }
}
