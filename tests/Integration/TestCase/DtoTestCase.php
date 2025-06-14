<?php
declare(strict_types = 1);
/**
 * /tests/Integration/TestCase/DtoTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\TestCase;

use App\DTO\RestDtoInterface;
use App\Tests\Utils\PhpUnitUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Throwable;
use TypeError;
use function array_filter;
use function array_map;
use function count;
use function explode;
use function gettype;
use function is_object;
use function preg_replace;
use function sprintf;
use function str_contains;
use function ucfirst;

/**
 * @package App\Tests\Integration\TestCase
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class DtoTestCase extends KernelTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<RestDtoInterface>
     */
    protected static string $dtoClass;

    /**
     * @throws Throwable
     */
    #[TestDox('Test that DTO class properties have `getter` methods defined')]
    public function testThatPropertiesHaveGetters(): void
    {
        $dtoReflection = new ReflectionClass(static::$dtoClass);

        foreach (self::getDtoProperties() as $reflectionProperty) {
            $method = 'get' . ucfirst($reflectionProperty->getName());

            $message = sprintf(
                "REST DTO class '%s' does not have required getter method '%s' for property '%s'.",
                static::$dtoClass,
                $method,
                $reflectionProperty->getName()
            );

            self::assertTrue($dtoReflection->hasMethod($method), $message);
        }
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that DTO class properties have `setter` methods defined')]
    public function testThatPropertiesHaveSetters(): void
    {
        $dtoReflection = new ReflectionClass(static::$dtoClass);

        foreach (self::getDtoProperties() as $reflectionProperty) {
            $method = 'set' . ucfirst($reflectionProperty->getName());

            $message = sprintf(
                "REST DTO class '%s' does not have required setter method '%s' for property '%s'.",
                static::$dtoClass,
                $method,
                $reflectionProperty->getName()
            );

            self::assertTrue($dtoReflection->hasMethod($method), $message);
        }
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that DTO class `setter` methods calls `setVisited` method')]
    public function testThatSetterCallsSetVisitedMethod(): void
    {
        /** @psalm-var ReflectionClass<RestDtoInterface> $dtoReflection */
        $dtoReflection = new ReflectionClass(static::$dtoClass);
        $properties = self::getDtoProperties();

        /** @psalm-var RestDtoInterface&MockObject $mock */
        $mock = $this->getMockBuilder(static::$dtoClass)
            ->onlyMethods(['setVisited'])
            ->getMock();

        $mock->expects(self::atLeast(count($properties)))
            ->method('setVisited');

        $expectedVisited = [];

        foreach ($properties as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter method for current property
            $setter = 'set' . ucfirst($reflectionProperty->getName());

            // Call setter method
            $mock->{$setter}($value);
        }

        self::assertSame($expectedVisited, $mock->getVisited());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that DTO class `getter` method returns expected value after `setter` method call')]
    public function testThatGetterMethodReturnExpectedAfterSetter(): void
    {
        /** @psalm-var ReflectionClass<RestDtoInterface> $dtoReflection */
        $dtoReflection = new ReflectionClass(static::$dtoClass);

        $dto = new static::$dtoClass();

        foreach (self::getDtoProperties() as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter and getter methods for current property
            $setter = 'set' . ucfirst($reflectionProperty->getName());
            $getter = 'get' . ucfirst($reflectionProperty->getName());

            // Call setter method
            $dto->{$setter}($value);

            self::assertSame($value, $dto->{$getter}());
        }
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterOnlyAcceptSpecifiedType')]
    #[TestDox('Test that `setter` method for `$field` will fail if parameter is not `$type` type')]
    public function testThatSetterOnlyAcceptSpecifiedType(string $field, string $type): void
    {
        // Get "valid" value for current property
        $value = PhpUnitUtil::getInvalidValueForType($type);

        $this->expectException(TypeError::class);

        $setter = 'set' . ucfirst($field);

        $dto = new static::$dtoClass();
        $dto->{$setter}($value);

        $message = sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            is_object($value) ? gettype($value) : '(' . gettype($value) . ')' . $value
        );

        self::fail($message);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     *
     * @throws Throwable
     */
    public static function dataProviderTestThatSetterOnlyAcceptSpecifiedType(): array
    {
        $iterator = static fn (ReflectionProperty $reflectionProperty): array => [
            $reflectionProperty->getName(),
            self::getType(static::$dtoClass, $reflectionProperty->getName()),
        ];

        return array_map($iterator, self::getDtoProperties());
    }

    private static function initializePropertyExtractor(): PropertyInfoExtractor
    {
        static $cache;

        if ($cache === null) {
            // a full list of extractors is shown further below
            $phpDocExtractor = new PhpDocExtractor();
            $reflectionExtractor = new ReflectionExtractor();

            // list of PropertyListExtractorInterface (any iterable)
            $listExtractors = [$reflectionExtractor];

            // list of PropertyTypeExtractorInterface (any iterable)
            $typeExtractors = [$phpDocExtractor, $reflectionExtractor];

            // list of PropertyDescriptionExtractorInterface (any iterable)
            $descriptionExtractors = [$phpDocExtractor];

            // list of PropertyAccessExtractorInterface (any iterable)
            $accessExtractors = [$reflectionExtractor];

            // list of PropertyInitializableExtractorInterface (any iterable)
            $propertyInitializableExtractors = [$reflectionExtractor];

            $cache = new PropertyInfoExtractor(
                $listExtractors,
                $typeExtractors,
                $descriptionExtractors,
                $accessExtractors,
                $propertyInitializableExtractors
            );
        }

        self::assertInstanceOf(PropertyInfoExtractor::class, $cache);

        return $cache;
    }

    /**
     * @phpstan-param ReflectionClass<RestDtoInterface> $dtoReflection
     *
     * @throws Throwable
     */
    private function getValueForProperty(ReflectionClass $dtoReflection, ReflectionProperty $reflectionProperty): mixed
    {
        return PhpUnitUtil::getValidValueForType(
            self::getType($dtoReflection->getName(), $reflectionProperty->getName())
        );
    }

    /**
     * @return array<int, ReflectionProperty>
     *
     * @throws Throwable
     */
    private static function getDtoProperties(): array
    {
        $dtoClass = static::$dtoClass;
        $dtoReflection = new ReflectionClass($dtoClass);
        $dto = new $dtoClass();

        $filter = static fn (ReflectionProperty $reflectionProperty): bool =>
            !$reflectionProperty->isStatic()
            && !$reflectionProperty->isPrivate()
            && (
                $dtoClass === $reflectionProperty->getDeclaringClass()->getName()
                || $reflectionProperty->getDeclaringClass()->isInstance($dto)
            );

        return array_filter($dtoReflection->getProperties(), $filter);
    }

    private static function getType(string $class, string $property): string
    {
        $propertyInfo = self::initializePropertyExtractor();

        $type = $propertyInfo->getType($class, $property);
        $type = preg_replace('/^array<.*>$/', 'array', (string)$type);

        self::assertNotNull($type);

        if (str_contains($type, '|')) {
            $type = explode('|', $type)[1];
        }

        return $type;
    }
}
