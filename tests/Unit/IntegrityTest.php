<?php
declare(strict_types = 1);
/**
 * /tests/Unit/IntegrityTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit;

use App\AutoMapper\RestRequestMapper;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Rest\Interfaces\ControllerInterface;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\StringableArrayObject;
use Closure;
use Doctrine\DBAL\Types\Type;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
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
 * Class IntegrityTest
 *
 * @package App\Tests\Unit
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IntegrityTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatControllerHasE2ETests
     *
     * @testdox Test that controller $controllerClass has E2E test class $controllerTestClass
     */
    public function testThatControllerHasE2ETests(string $controllerTestClass, string $controllerClass): void
    {
        $message = sprintf(
            'Controller "%s" does not have required test class "%s".',
            $controllerClass,
            $controllerTestClass
        );

        static::assertTrue(class_exists($controllerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRestControllerHaveIntegrationTests
     *
     * @testdox Test that REST controller $controllerClass has integration test class $controllerTestClass
     */
    public function testThatRestControllerHaveIntegrationTests(
        string $controllerTestClass,
        string $controllerClass
    ): void {
        $message = sprintf(
            'Controller "%s" does not have required test class "%s".',
            $controllerClass,
            $controllerTestClass
        );

        static::assertTrue(class_exists($controllerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRepositoryClassHasIntegrationTests
     *
     * @testdox Test that repository `$repository` has integration test class `$testClass`.
     */
    public function testThatRepositoryClassHasIntegrationTests(string $testClass, string $repository): void
    {
        $format = <<<FORMAT
Repository '%s' doesn't have required test class '%s'.
FORMAT;

        $message = sprintf(
            $format,
            $repository,
            $testClass
        );

        static::assertTrue(class_exists($testClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRepositoryHaveFunctionalTests
     *
     * @testdox Test that repository `$repository` has functional test class `$testClass` for `$methods` methods.
     */
    public function testThatRepositoryHaveFunctionalTests(
        string $testClass,
        string $repository,
        StringableArrayObject $methods
    ): void {
        $format = <<<FORMAT
Repository '%s' doesn't have required test class '%s', repository has following methods that needs to be tested: '%s'.
FORMAT;

        $message = sprintf(
            $format,
            $repository,
            $testClass,
            implode('", "', $methods->getArrayCopy())
        );

        static::assertTrue(class_exists($testClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRestRepositoryHaveIntegrationTests
     *
     * @testdox Test that repository $repositoryClass has integration test class $repositoryTestClass
     */
    public function testThatRestRepositoryHaveIntegrationTests(
        string $repositoryTestClass,
        string $repositoryClass
    ): void {
        $message = sprintf(
            'Repository "%s" does not have required test class "%s".',
            $repositoryClass,
            $repositoryTestClass
        );

        static::assertTrue(class_exists($repositoryTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatEntityHaveIntegrationTests
     *
     * @testdox Test that entity $entityClass has integration test class $entityTestClass
     */
    public function testThatEntityHaveIntegrationTests(string $entityTestClass, string $entityClass): void
    {
        $message = sprintf(
            'Entity "%s" does not have required test class "%s".',
            $entityClass,
            $entityTestClass
        );

        static::assertTrue(class_exists($entityTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatEventSubscriberHaveIntegrationTest
     *
     * @testdox Test that event subscriber $eventSubscriberClass has integration test class $eventSubscriberTestClass
     */
    public function testThatEventSubscriberHaveIntegrationTest(
        string $eventSubscriberTestClass,
        string $eventSubscriberClass
    ): void {
        $message = sprintf(
            'EventSubscriber "%s" does not have required test class "%s".',
            $eventSubscriberClass,
            $eventSubscriberTestClass
        );

        static::assertTrue(class_exists($eventSubscriberTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatEventListenerHaveIntegrationTest
     *
     * @testdox Test that event listener $eventListenerClass has integration test class $eventListenerTestClass
     */
    public function testThatEventListenerHaveIntegrationTest(
        string $eventListenerTestClass,
        string $eventListenerClass
    ): void {
        $message = sprintf(
            'EventListener "%s" does not have required test class "%s".',
            $eventListenerClass,
            $eventListenerTestClass
        );

        static::assertTrue(class_exists($eventListenerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatResourceHaveIntegrationTest
     *
     * @testdox Test that resource $resourceClass has integration test class $resourceTestClass
     */
    public function testThatResourceHaveIntegrationTest(string $resourceTestClass, string $resourceClass): void
    {
        $message = sprintf(
            'Resource "%s" does not have required test class "%s".',
            $resourceClass,
            $resourceTestClass
        );

        static::assertTrue(class_exists($resourceTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatSecurityAuthenticatorHaveIntegrationTest
     *
     * @testdox Test that authenticator $authenticatorClass has integration test class $authenticatorTestClass
     */
    public function testThatSecurityAuthenticatorHaveIntegrationTest(
        string $authenticatorTestClass,
        string $authenticatorClass
    ): void {
        $message = sprintf(
            'Authenticator "%s" does not have required test class "%s".',
            $authenticatorClass,
            $authenticatorTestClass
        );

        static::assertTrue(class_exists($authenticatorTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatSecurityProvidersHaveIntegrationTest
     *
     * @testdox Test that security provider $providerClass has integration test class $providerTestClass
     */
    public function testThatSecurityProvidersHaveIntegrationTest(string $providerTestClass, string $providerClass): void
    {
        $message = sprintf(
            'Security provider "%s" does not have required test class "%s".',
            $providerClass,
            $providerTestClass
        );

        static::assertTrue(class_exists($providerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatSecurityVoterHaveIntegrationTest
     *
     * @testdox Test that security voter $voterClass has integration test class $voterTestClass
     */
    public function testThatSecurityVoterHaveIntegrationTest(string $voterTestClass, string $voterClass): void
    {
        $message = sprintf(
            'Security voter "%s" does not have required test class "%s".',
            $voterClass,
            $voterTestClass
        );

        static::assertTrue(class_exists($voterTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatDtoHaveIntegrationTest
     *
     * @testdox Test that REST DTO $dtoClass has integration test class $dtoTestClass
     */
    public function testThatDtoHaveIntegrationTest(string $dtoTestClass, string $dtoClass): void
    {
        $message = sprintf(
            'REST DTO "%s" does not have required test class "%s".',
            $dtoClass,
            $dtoTestClass
        );

        static::assertTrue(class_exists($dtoTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatFormTypeHaveIntegrationTest
     *
     * @testdox Test that form type $formTypeClass has integration test class $formTypeTestClass
     */
    public function testThatFormTypeHaveIntegrationTest(string $formTypeTestClass, string $formTypeClass): void
    {
        $message = sprintf(
            'Form type "%s" does not have required test class "%s".',
            $formTypeClass,
            $formTypeTestClass
        );

        static::assertTrue(class_exists($formTypeTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatDataTransformerHaveIntegrationTest
     *
     * @testdox Test that data transformer $dataTransformerClass has integration test class $dataTransformerTestClass
     */
    public function testThatDataTransformerHaveIntegrationTest(
        string $dataTransformerTestClass,
        string $dataTransformerClass
    ): void {
        $message = sprintf(
            'DataTransformer "%s" does not have required test class "%s".',
            $dataTransformerClass,
            $dataTransformerTestClass
        );

        static::assertTrue(class_exists($dataTransformerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatConstraintHasUnitTest
     *
     * @param string $constraintClass
     *
     * @testdox Test that constraint `$constraintClass` has unit test class `$constraintTestClass`
     */
    public function testThatConstraintHasUnitTest(string $constraintTestClass, $constraintClass): void
    {
        $message = sprintf(
            'Constraint "%s" does not have required test class "%s".',
            $constraintClass,
            $constraintTestClass
        );

        static::assertTrue(class_exists($constraintTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatValidatorConstraintsHaveIntegrationTest
     *
     * @testdox Test that validator $validatorClass has integration test class $validatorTestClass
     */
    public function testThatValidatorConstraintsHaveIntegrationTest(
        string $validatorTestClass,
        string $validatorClass
    ): void {
        $message = sprintf(
            'Validator "%s" does not have required test class "%s".',
            $validatorClass,
            $validatorTestClass
        );

        static::assertTrue(class_exists($validatorTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatCustomDBALTypeHaveIntegrationTest
     *
     * @testdox Test that DBAL type $dbalTypeClass has integration test class $dbalTypeTestClass
     */
    public function testThatCustomDBALTypeHaveIntegrationTest(
        string $dbalTypeTestClass,
        string $dbalTypeClass
    ): void {
        $message = sprintf(
            'DBAL type "%s" does not have required test class "%s".',
            $dbalTypeClass,
            $dbalTypeTestClass
        );

        static::assertTrue(class_exists($dbalTypeTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatRestRequestMapperHaveIntegrationTest
     *
     * @testdox Test that REST request mapper $restRequestMapper has integration test class $restRequestMapperTestClass
     */
    public function testThatRestRequestMapperHaveIntegrationTest(
        string $restRequestMapperTestClass,
        string $restRequestMapper
    ): void {
        $message = sprintf(
            'REST request mapper "%s" does not have required test class "%s".',
            $restRequestMapper,
            $restRequestMapperTestClass
        );

        static::assertTrue(class_exists($restRequestMapperTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatGenericServiceHaveIntegrationTests
     *
     * @testdox Test that generic service $serviceClass has integration test class $serviceTestClass
     */
    public function testThatGenericServiceHaveIntegrationTests(
        string $serviceTestClass,
        string $serviceClass
    ): void {
        $message = sprintf(
            'Service "%s" does not have required test class "%s".',
            $serviceClass,
            $serviceTestClass
        );

        static::assertTrue(class_exists($serviceTestClass), $message);
    }

    public function dataProviderTestThatControllerHasE2ETests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Controller/';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\E2E\\Controller\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    public function dataProviderTestThatRepositoryClassHasIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';
        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';

        $filter = $this->getInterfaceFilter(BaseRepositoryInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatRepositoryHaveFunctionalTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Functional\\Repository\\';

        $repositoryMethods = [];

        $filter = static function (ReflectionClass $reflectionClass) use (&$repositoryMethods): bool {
            $filter = fn (ReflectionMethod $method): bool =>
                $method->class === $reflectionClass->getName() && !$method->isConstructor();
            $formatter = fn (ReflectionMethod $method): string => $method->getName();

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
            $file = $reflectionClass->getFileName();

            $base = str_replace([$folder, DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . str_replace('.php', '', $base);
            $classTest = $namespaceTest . str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
                new StringableArrayObject($repositoryMethods[$reflectionClass->getName()]),
            ];
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter, $formatter);
    }

    public function dataProviderTestThatRestRepositoryHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';

        $filter = $this->getInterfaceFilter(BaseRepositoryInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatEntityHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Entity/';

        $namespace = '\\App\\Entity\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Entity\\';

        $filter = $this->getInterfaceFilter(EntityInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatEventSubscriberHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/EventSubscriber/';

        $namespace = '\\App\\EventSubscriber\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventSubscriber\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    public function dataProviderTestThatEventListenerHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/EventListener/';

        $namespace = '\\App\\EventListener\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventListener\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    public function dataProviderTestThatResourceHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Resource/';

        $namespace = '\\App\\Resource\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Resource\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    public function dataProviderTestThatSecurityAuthenticatorHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Security/Authenticator/';

        $namespace = '\\App\\Security\\Authenticator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Authenticator\\';

        $filter = fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isAbstract()
            && !$reflectionClass->isInterface()
            && $reflectionClass->implementsInterface(AuthenticatorInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatSecurityProvidersHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Security/Provider/';

        $namespace = '\\App\\Security\\Provider\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Provider\\';

        $filter = $this->getInterfaceFilter(UserProviderInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatSecurityVoterHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Security/Voter/';

        $namespace = '\\App\\Security\\Voter\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Voter\\';

        $filter = $this->getInterfaceFilter(VoterInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatDtoHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/DTO/';

        $namespace = '\\App\\DTO\\';
        $namespaceTest = '\\App\\Tests\\Integration\\DTO\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    public function dataProviderTestThatFormTypeHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Form/';

        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';

        $filter = $this->getInterfaceFilter(FormTypeInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatDataTransformerHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Form/';

        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';

        $filter = $this->getInterfaceFilter(DataTransformerInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatRestControllerHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Controller/';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Controller\\';

        $filter = $this->getInterfaceFilter(ControllerInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatConstraintHasUnitTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Validator/';

        $namespace = '\\App\\Validator\\';
        $namespaceTest = '\\App\\Tests\\Unit\\Validator\\';

        $filter = $this->getSubclassOfFilter(Constraint::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatValidatorConstraintsHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Validator/';

        $namespace = '\\App\\Validator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Validator\\';

        $filter = $this->getInterfaceFilter(ConstraintValidatorInterface::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatCustomDBALTypeHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Doctrine/DBAL/Types/';

        $namespace = '\\App\\Doctrine\\DBAL\\Types\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Doctrine\\DBAL\\Types\\';

        $filter = fn (ReflectionClass $reflectionClass): bool =>
            !$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(Type::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatRestRequestMapperHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/AutoMapper/';

        $namespace = '\\App\\AutoMapper\\';
        $namespaceTest = '\\App\\Tests\\Integration\\AutoMapper\\';

        $filter = fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isAbstract()
            && !$reflectionClass->isTrait()
            && $reflectionClass->isSubclassOf(RestRequestMapper::class);

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    public function dataProviderTestThatGenericServiceHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Service/';

        $namespace = '\\App\\Service\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Service\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    private function getTestCases(
        string $folder,
        string $namespace,
        string $namespaceTest,
        ?Closure $filter = null,
        ?Closure $formatter = null
    ): array {
        $pattern = '/^.+\.php$/i';

        $filter ??= $filter ?? $filter = fn (ReflectionClass $reflectionClass): bool =>
                !$reflectionClass->isInterface() && !$reflectionClass->isAbstract() && !$reflectionClass->isTrait();

        $formatter ??= $this->getFormatterClosure($folder, $namespace, $namespaceTest);
        $iterator = $this->getReflectionClass($folder, $namespace);

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

    private function getReflectionClass(string $folder, string $namespace): Closure
    {
        return fn (string $file): ReflectionClass => new ReflectionClass(
            $namespace . str_replace([$folder, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file)
        );
    }

    /**
     * Formatter closure to return an array which contains names of expected test class and actual class.
     */
    private function getFormatterClosure(string $folder, string $namespace, string $namespaceTest): Closure
    {
        return static function (ReflectionClass $reflectionClass) use ($folder, $namespace, $namespaceTest): array {
            $file = $reflectionClass->getFileName();

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
    private function bootKernelCached(): void
    {
        static $cache = null;

        if ($cache === null) {
            static::bootKernel();

            $cache = true;
        }
    }

    private function getInterfaceFilter(string $interface): Closure
    {
        return fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isInterface()
            && !$reflectionClass->isAbstract()
            && $reflectionClass->implementsInterface($interface);
    }

    private function getSubclassOfFilter(string $class): Closure
    {
        return fn (ReflectionClass $reflectionClass): bool => !$reflectionClass->isInterface()
            && $reflectionClass->isSubclassOf($class);
    }
}
