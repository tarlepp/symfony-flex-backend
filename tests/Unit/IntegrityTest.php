<?php
declare(strict_types = 1);
/**
 * /tests/Unit/IntegrityTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit;

use App\AutoMapper\RestRequestMapper;
use App\Entity\EntityInterface;
use App\Rest\ControllerInterface;
use App\Rest\RepositoryInterface;
use App\Utils\Tests\PhpUnitUtil;
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
use Symfony\Component\Validator\ConstraintValidatorInterface;
use function array_filter;
use function array_map;
use function class_exists;
use function implode;
use function sprintf;
use function str_replace;
use const DIRECTORY_SEPARATOR;

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
     * @param string $controllerTestClass
     * @param string $controllerClass
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

        $message = sprintf(
            $format,
            $repositoryClass,
            $repositoryTestClass,
            implode('", "', $methods)
        );

        static::assertTrue(class_exists($repositoryTestClass), $message);
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
     * @param string $entityTestClass
     * @param string $entityClass
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
     * @param string $eventSubscriberTestClass
     * @param string $eventSubscriberClass
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
     * @param string $eventListenerTestClass
     * @param string $eventListenerClass
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
     * @param string $resourceTestClass
     * @param string $resourceClass
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
     * @param string $authenticatorTestClass
     * @param string $authenticatorClass
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
     * @param string $providerTestClass
     * @param string $providerClass
     */
    public function testThatSecurityProvidersHaveIntegrationTest(string $providerTestClass, string $providerClass): void
    {
        $message = sprintf(
            'Resource "%s" does not have required test class "%s".',
            $providerClass,
            $providerTestClass
        );

        static::assertTrue(class_exists($providerTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatSecurityVoterHaveIntegrationTest
     *
     * @param string $voterTestClass
     * @param string $voterClass
     */
    public function testThatSecurityVoterHaveIntegrationTest(string $voterTestClass, string $voterClass): void
    {
        $message = sprintf(
            'Resource "%s" does not have required test class "%s".',
            $voterClass,
            $voterTestClass
        );

        static::assertTrue(class_exists($voterTestClass), $message);
    }

    /**
     * @dataProvider dataProviderTestThatDtoHaveIntegrationTest
     *
     * @param string $dtoTestClass
     * @param string $dtoClass
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
     * @param string $formTypeTestClass
     * @param string $formTypeClass
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
     * @param string $dataTransformerTestClass
     * @param string $dataTransformerClass
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
     * @dataProvider dataProviderTestThatValidatorConstraintsHaveIntegrationTest
     *
     * @param string $validatorTestClass
     * @param string $validatorClass
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
     * @param string $dbalTypeTestClass
     * @param string $dbalTypeClass
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
     * @param string $restRequestMapperTestClass
     * @param string $restRequestMapper
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
     * @return array
     */
    public function dataProviderTestThatControllerHasE2ETests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Controller/';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\E2E\\Controller\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRepositoryHaveFunctionalTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Functional\\Repository\\';

        $repositoryMethods = [];

        $filter = static function (ReflectionClass $reflectionClass) use (&$repositoryMethods) {
            $filter = static function (ReflectionMethod $method) use ($reflectionClass) {
                return $method->class === $reflectionClass->getName();
            };

            $methods = array_filter($reflectionClass->getMethods(), $filter);

            $formatter = static function (ReflectionMethod $method) {
                return $method->getName();
            };

            $repositoryMethods[$reflectionClass->getName()] = array_map($formatter, $methods);

            return !(
                $reflectionClass->isAbstract() ||
                $reflectionClass->isInterface() ||
                $reflectionClass->isTrait() ||
                empty($methods)
            );
        };

        $formatter = static function (ReflectionClass $reflectionClass) use (
            &$repositoryMethods,
            $folder,
            $namespace,
            $namespaceTest
        ) {
            $file = $reflectionClass->getFileName();

            $base = str_replace([$folder, DIRECTORY_SEPARATOR], ['', '\\'], $file);
            $class = $namespace . str_replace('.php', '', $base);
            $classTest = $namespaceTest . str_replace('.php', 'Test', $base);

            return [
                $classTest,
                $class,
                $repositoryMethods[$reflectionClass->getName()],
            ];
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter, $formatter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRestRepositoryHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Repository/';

        $namespace = '\\App\\Repository\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Repository\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(RepositoryInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatEntityHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Entity/';

        $namespace = '\\App\\Entity\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Entity\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isInterface() && $reflectionClass->implementsInterface(EntityInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatEventSubscriberHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/EventSubscriber/';

        $namespace = '\\App\\EventSubscriber\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventSubscriber\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatEventListenerHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/EventListener/';

        $namespace = '\\App\\EventListener\\';
        $namespaceTest = '\\App\\Tests\\Integration\\EventListener\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatResourceHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Resource/';

        $namespace = '\\App\\Resource\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Resource\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSecurityAuthenticatorHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Security/Authenticator/';

        $namespace = '\\App\\Security\\Authenticator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Authenticator\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract()
                && !$reflectionClass->isInterface()
                && $reflectionClass->implementsInterface(AuthenticatorInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSecurityProvidersHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Security/Provider/';

        $namespace = '\\App\\Security\\Provider\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Provider\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract()
                && !$reflectionClass->isInterface()
                && $reflectionClass->implementsInterface(UserProviderInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSecurityVoterHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Security/Voter/';

        $namespace = '\\App\\Security\\Voter\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Security\\Voter\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract()
                && !$reflectionClass->isInterface()
                && $reflectionClass->implementsInterface(VoterInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatDtoHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/DTO/';

        $namespace = '\\App\\DTO\\';
        $namespaceTest = '\\App\\Tests\\Integration\\DTO\\';

        return $this->getTestCases($folder, $namespace, $namespaceTest);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatFormTypeHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Form/';

        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract() && $reflectionClass->implementsInterface(FormTypeInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatDataTransformerHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Form/';

        $namespace = '\\App\\Form\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Form\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(DataTransformerInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRestControllerHaveIntegrationTests(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Controller/';

        $namespace = '\\App\\Controller\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Controller\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(ControllerInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatValidatorConstraintsHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Validator/';

        $namespace = '\\App\\Validator\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Validator\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return $reflectionClass->implementsInterface(ConstraintValidatorInterface::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCustomDBALTypeHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/Doctrine/DBAL/Types/';

        $namespace = '\\App\\Doctrine\\DBAL\\Types\\';
        $namespaceTest = '\\App\\Tests\\Integration\\Doctrine\\DBAL\\Types\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(Type::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRestRequestMapperHaveIntegrationTest(): array
    {
        $this->bootKernelCached();

        $folder = static::$kernel->getProjectDir() . '/src/AutoMapper/';

        $namespace = '\\App\\AutoMapper\\';
        $namespaceTest = '\\App\\Tests\\Integration\\AutoMapper\\';

        $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isAbstract()
                && !$reflectionClass->isTrait()
                && $reflectionClass->isSubclassOf(RestRequestMapper::class);
        };

        return $this->getTestCases($folder, $namespace, $namespaceTest, $filter);
    }

    /**
     * @param string       $folder
     * @param string       $namespace
     * @param string       $namespaceTest
     * @param Closure|null $filter
     * @param Closure|null $formatter
     *
     * @return array
     */
    private function getTestCases(
        string $folder,
        string $namespace,
        string $namespaceTest,
        ?Closure $filter = null,
        ?Closure $formatter = null
    ): array {
        $pattern = '/^.+\.php$/i';

        $filter = $filter ?? $filter ?? $filter = static function (ReflectionClass $reflectionClass) {
            return !$reflectionClass->isInterface() && !$reflectionClass->isAbstract() && !$reflectionClass->isTrait();
        };

        $formatter = $formatter ?? $this->getFormatterClosure($folder, $namespace, $namespaceTest);
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

    /**
     * @param string $folder
     * @param string $namespace
     *
     * @return Closure
     */
    private function getReflectionClass(string $folder, string $namespace): Closure
    {
        return static function (string $file) use ($folder, $namespace) {
            $class = $namespace . str_replace([$folder, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file);

            return new ReflectionClass($class);
        };
    }

    /**
     * Formatter closure to return an array which contains names of expected test class and actual class.
     *
     * @param string $folder
     * @param string $namespace
     * @param string $namespaceTest
     *
     * @return Closure
     */
    private function getFormatterClosure(string $folder, string $namespace, string $namespaceTest): Closure
    {
        return static function (ReflectionClass $reflectionClass) use ($folder, $namespace, $namespaceTest) {
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
}
