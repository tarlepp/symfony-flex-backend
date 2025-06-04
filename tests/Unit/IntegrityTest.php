<?php
declare(strict_types = 1);
/**
 * /tests/Unit/IntegrityTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit;

use App\AutoMapper\RestRequestMapper;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Rest\Interfaces\ControllerInterface;
use App\Tests\Utils\PhpUnitUtil;
use App\Tests\Utils\StringableArrayObject;
use Closure;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use function array_filter;
use function array_map;
use function class_exists;
use function count;
use function implode;
use function sprintf;
use function str_replace;
use const DIRECTORY_SEPARATOR;

/**
 * @package App\Tests\Unit
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class IntegrityTest extends KernelTestCase
{
    public static function getKernel(): KernelInterface
    {
        self::bootKernelCached();

        if (self::$kernel === null) {
            throw new RuntimeException('Kernel is not booting.');
        }

        return self::$kernel;
    }

    #[DataProvider('dataProviderTestThatControllerHasE2ETests')]
    #[TestDox('Test that controller `$class` has E2E test class `$testClass`')]
    public function testThatControllerHasE2ETests(string $testClass, string $class): void
    {
        $message = sprintf(
            'Controller "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatRestControllerHaveIntegrationTests')]
    #[TestDox('Test that REST controller `$class` has integration test class `$testClass`')]
    public function testThatRestControllerHaveIntegrationTests(string $testClass, string $class): void
    {
        $message = sprintf(
            'Controller "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatRepositoryClassHasIntegrationTests')]
    #[TestDox('Test that repository `$class` has integration test class `$testClass`')]
    public function testThatRepositoryClassHasIntegrationTests(string $testClass, string $class): void
    {
        $format = <<<FORMAT
Repository '%s' doesn't have required test class '%s'.
FORMAT;

        $message = sprintf(
            $format,
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatRepositoryHaveFunctionalTests')]
    #[TestDox('Test that repository `$class` has functional test class `$testClass` for `$methods` methods')]
    public function testThatRepositoryHaveFunctionalTests(
        string $testClass,
        string $class,
        StringableArrayObject $methods
    ): void {
        $format = <<<FORMAT
Repository '%s' doesn't have required test class '%s', repository has following methods that needs to be tested: '%s'.
FORMAT;

        $message = sprintf(
            $format,
            $class,
            $testClass,
            implode('", "', $methods->getArrayCopy())
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatRestRepositoryHaveIntegrationTests')]
    #[TestDox('Test that repository `$class` has integration test class `$testClass`')]
    public function testThatRestRepositoryHaveIntegrationTests(string $testClass, string $class): void
    {
        $message = sprintf(
            'Repository "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatEntityHaveIntegrationTests')]
    #[TestDox('Test that entity `$class` has integration test class `$testClass`')]
    public function testThatEntityHaveIntegrationTests(string $testClass, string $class): void
    {
        $message = sprintf(
            'Entity "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatEventSubscriberHaveIntegrationTest')]
    #[TestDox('Test that event subscriber `$class` has integration test class `$testClass`')]
    public function testThatEventSubscriberHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'EventSubscriber "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatEventListenerHaveIntegrationTest')]
    #[TestDox('Test that event listener `$class` has integration test class `$testClass`')]
    public function testThatEventListenerHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'EventListener "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatResourceHaveIntegrationTest')]
    #[TestDox('Test that resource `$class` has integration test class `$testClass`')]
    public function testThatResourceHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Resource "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatSecurityAuthenticatorHaveIntegrationTest')]
    #[TestDox('Test that authenticator `$class` has integration test class `$testClass`')]
    public function testThatSecurityAuthenticatorHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Authenticator "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatSecurityProvidersHaveIntegrationTest')]
    #[TestDox('Test that security provider `$class` has integration test class `$testClass`')]
    public function testThatSecurityProvidersHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Security provider "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatSecurityVoterHaveIntegrationTest')]
    #[TestDox('Test that security voter `$class` has integration test class `$testClass`')]
    public function testThatSecurityVoterHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Security voter "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatDtoHaveIntegrationTest')]
    #[TestDox('Test that REST DTO `$class` has integration test class `$testClass`')]
    public function testThatDtoHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'REST DTO "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatFormTypeHaveIntegrationTest')]
    #[TestDox('Test that form type `$class` has integration test class `$testClass`')]
    public function testThatFormTypeHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Form type "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatDataTransformerHaveIntegrationTest')]
    #[TestDox('Test that data transformer `$class` has integration test class `$testClass`')]
    public function testThatDataTransformerHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'DataTransformer "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatConstraintHasUnitTest')]
    #[TestDox('Test that constraint `$class` has unit test class `$testClass`')]
    public function testThatConstraintHasUnitTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Constraint "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatEventSubscriberHasUnitTest')]
    #[TestDox('Test that EventSubscriber `$eventSubscriberClass` has unit test class `$eventSubscriberTestClass`')]
    public function testThatEventSubscriberHasUnitTest(
        string $eventSubscriberTestClass,
        string $eventSubscriberClass
    ): void {
        $message = sprintf(
            'EventSubscriber "%s" does not have required test class "%s".',
            $eventSubscriberClass,
            $eventSubscriberTestClass
        );

        self::assertTrue(class_exists($eventSubscriberTestClass), $message);
    }

    #[DataProvider('dataProviderTestThatValidatorConstraintsHaveIntegrationTest')]
    #[TestDox('Test that validator `$class` has integration test class `$testClass`')]
    public function testThatValidatorConstraintsHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Validator "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatCustomDBALTypeHaveIntegrationTest')]
    #[TestDox('Test that DBAL type `$class` has integration test class `$testClass`')]
    public function testThatCustomDBALTypeHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'DBAL type "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatRestRequestMapperHaveIntegrationTest')]
    #[TestDox('Test that REST request mapper `$class` has integration test class `$testClass`')]
    public function testThatRestRequestMapperHaveIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'REST request mapper "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatGenericServiceHaveIntegrationTests')]
    #[TestDox('Test that generic service `$class` has integration test class `$testClass`')]
    public function testThatGenericServiceHaveIntegrationTests(string $testClass, string $class): void
    {
        $message = sprintf(
            'Service "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    #[DataProvider('dataProviderTestThatValueResolverServiceHasIntegrationTest')]
    #[TestDox('Test that argument value resolver service `$class` has integration test class `$testClass`')]
    public function testThatValueResolverServiceHasIntegrationTest(string $testClass, string $class): void
    {
        $message = sprintf(
            'Argument value resolver service "%s" does not have required test class "%s".',
            $class,
            $testClass
        );

        self::assertTrue(class_exists($testClass), $message);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatControllerHasE2ETests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Controller/';
        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\E2E\\Controller\\';

        return self::getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatRepositoryClassHasIntegrationTests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Repository/';
        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';
        $filter = self::getInterfaceFilter(BaseRepositoryInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: StringableArrayObject}>
     */
    public static function dataProviderTestThatRepositoryHaveFunctionalTests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Repository/';
        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Functional\\Repository\\';

        $repositoryMethods = [];

        $filter = static function (ReflectionClass $reflectionClass) use (&$repositoryMethods): bool {
            $filter = static fn (ReflectionMethod $method): bool =>
                $method->class === $reflectionClass->getName() && !$method->isConstructor();
            $formatter = static fn (ReflectionMethod $method): string => $method->getName();

            $methods = array_values(array_filter($reflectionClass->getMethods(), $filter));

            $repositoryMethods[$reflectionClass->getName()] = array_map($formatter, $methods);

            return !(
                $reflectionClass->isAbstract() ||
                $reflectionClass->isInterface() ||
                $reflectionClass->isTrait() ||
                count($methods) === 0
            );
        };

        $formatter = static function (ReflectionClass $reflectionClass) use (
            &$repositoryMethods,
            $folder,
            $namespace,
            $namespaceTest
        ): array {
            $file = (string)$reflectionClass->getFileName();

            $base = str_replace([$folder, DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . str_replace('.php', '', $base);
            $classTest = $namespaceTest . str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
                new StringableArrayObject($repositoryMethods[$reflectionClass->getName()]),
            ];
        };

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter, $formatter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatRestRepositoryHaveIntegrationTests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Repository/';
        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';
        $filter = self::getInterfaceFilter(BaseRepositoryInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatEntityHaveIntegrationTests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Entity/';
        $namespace = '\\App\\Entity\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Entity\\';
        $filter = self::getInterfaceFilter(EntityInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatEventSubscriberHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/EventSubscriber/';
        $namespace = '\\App\\EventSubscriber\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventSubscriber\\';

        return self::getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatEventListenerHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/EventListener/';
        $namespace = '\\App\\EventListener\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventListener\\';

        return self::getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatResourceHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Resource/';
        $namespace = '\\App\\Resource\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Resource\\';

        return self::getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatSecurityAuthenticatorHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Security/Authenticator/';
        $namespace = '\\App\\Security\\Authenticator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Authenticator\\';
        $filter = static fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isAbstract()
            && !$reflectionClass->isInterface()
            && $reflectionClass->implementsInterface(AuthenticatorInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatSecurityProvidersHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Security/Provider/';
        $namespace = '\\App\\Security\\Provider\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Provider\\';
        $filter = self::getInterfaceFilter(UserProviderInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatSecurityVoterHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Security/Voter/';
        $namespace = '\\App\\Security\\Voter\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Voter\\';
        $filter = self::getInterfaceFilter(VoterInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatDtoHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/DTO/';
        $namespace = '\\App\\DTO\\';
        $namespaceTest = '\\App\\Tests\\Integration\\DTO\\';

        return self::getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatFormTypeHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Form/';
        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';
        $filter = self::getInterfaceFilter(FormTypeInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatDataTransformerHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Form/';
        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';
        $filter = self::getInterfaceFilter(DataTransformerInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatRestControllerHaveIntegrationTests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Controller/';
        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Controller\\';
        $filter = self::getInterfaceFilter(ControllerInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatConstraintHasUnitTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Validator/';
        $namespace = '\\App\\Validator\\';
        $namespaceTest = '\\App\\Tests\\Unit\\Validator\\';
        $filter = self::getSubclassOfFilter(Constraint::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatEventSubscriberHasUnitTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/EventSubscriber/';
        $namespace = '\\App\\EventSubscriber\\';
        $namespaceTest = '\\App\\Tests\\Unit\\EventSubscriber\\';
        $filter = self::getInterfaceFilter(EventSubscriberInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatValidatorConstraintsHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Validator/';
        $namespace = '\\App\\Validator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Validator\\';
        $filter = self::getInterfaceFilter(ConstraintValidatorInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatCustomDBALTypeHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Doctrine/DBAL/Types/';
        $namespace = '\\App\\Doctrine\\DBAL\\Types\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Doctrine\\DBAL\\Types\\';
        $filter = static fn (ReflectionClass $reflectionClass): bool =>
            !$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(Type::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatRestRequestMapperHaveIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/AutoMapper/';
        $namespace = '\\App\\AutoMapper\\';
        $namespaceTest = '\\App\\Tests\\Integration\\AutoMapper\\';
        $filter = static fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isAbstract()
            && !$reflectionClass->isTrait()
            && $reflectionClass->isSubclassOf(RestRequestMapper::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatGenericServiceHaveIntegrationTests(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/Service/';
        $namespace = '\\App\\Service\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Service\\';

        return self::getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatValueResolverServiceHasIntegrationTest(): array
    {
        $folder = self::getKernel()->getProjectDir() . '/src/ValueResolver/';
        $namespace = '\\App\\ValueResolver\\';
        $namespaceTest = '\\App\\Tests\\Integration\\ValueResolver\\';
        $filter = self::getInterfaceFilter(ValueResolverInterface::class);

        return self::getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array<int, mixed>
     */
    private static function getTestCases(
        string $folder,
        string $namespace,
        string $namespaceTest,
        ?Closure $filter = null,
        ?Closure $formatter = null
    ): array {
        $pattern = '/^.+\.php$/i';

        $filter ??= static fn (ReflectionClass $r): bool => !$r->isInterface() && !$r->isAbstract() && !$r->isTrait();
        $formatter ??= self::getFormatterClosure($folder, $namespace, $namespaceTest);
        $iterator = self::getReflectionClass($folder, $namespace);

        return array_map(
            $formatter,
            array_filter(
                array_map(
                    $iterator,
                    PhpUnitUtil::recursiveFileSearch($folder, $pattern)
                ),
                $filter
            )
        );
    }

    private static function getReflectionClass(string $folder, string $namespace): Closure
    {
        return static function (string $file) use ($folder, $namespace): ReflectionClass {
            /** @psalm-var class-string $class */
            $class = $namespace . str_replace([$folder, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file);

            return new ReflectionClass($class);
        };
    }

    /**
     * Formatter closure to return an array which contains names of expected test class and actual class.
     */
    private static function getFormatterClosure(string $folder, string $namespace, string $namespaceTest): Closure
    {
        return static function (ReflectionClass $reflectionClass) use ($folder, $namespace, $namespaceTest): array {
            $file = (string)$reflectionClass->getFileName();
            $base = str_replace([$folder, DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . str_replace('.php', '', $base);
            $classTest = $namespaceTest . str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
            ];
        };
    }

    /**
     * Method to boot kernel as a cached one, no need to actually boot kernel each time within this test class.
     */
    private static function bootKernelCached(): void
    {
        static $cache = null;

        if ($cache === null) {
            self::bootKernel();

            $cache = true;
        }
    }

    /**
     * @param class-string $interface
     */
    private static function getInterfaceFilter(string $interface): Closure
    {
        return static fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isInterface()
            && !$reflectionClass->isAbstract()
            && $reflectionClass->implementsInterface($interface);
    }

    /**
     * @param class-string $class
     */
    private static function getSubclassOfFilter(string $class): Closure
    {
        return static fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isInterface()
            && $reflectionClass->isSubclassOf($class);
    }
}
