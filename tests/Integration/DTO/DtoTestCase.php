<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/DtoTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO;

use App\DTO\RestDtoInterface;
use App\Utils\Tests\PhpUnitUtil;
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
use function gettype;
use function is_object;
use function sprintf;
use function ucfirst;

/**
 * Class DtoTestCase
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class DtoTestCase extends KernelTestCase
{
    protected string $dtoClass;

    private static ?PropertyInfoExtractor $propertyInfo = null;

    /**
     * @throws Throwable
     */
    public function testThatPropertiesHaveGetters(): void
    {
        $dtoReflection = new ReflectionClass($this->dtoClass);

        foreach ($this->getDtoProperties() as $reflectionProperty) {
            $method = 'get' . ucfirst($reflectionProperty->getName());

            $message = sprintf(
                "REST DTO class '%s' does not have required getter method '%s' for property '%s'.",
                $this->dtoClass,
                $method,
                $reflectionProperty->getName()
            );

            static::assertTrue($dtoReflection->hasMethod($method), $message);
        }
    }

    /**
     * @throws Throwable
     */
    public function testThatPropertiesHaveSetters(): void
    {
        $dtoReflection = new ReflectionClass($this->dtoClass);

        foreach ($this->getDtoProperties() as $reflectionProperty) {
            $method = 'set' . ucfirst($reflectionProperty->getName());

            $message = sprintf(
                "REST DTO class '%s' does not have required setter method '%s' for property '%s'.",
                $this->dtoClass,
                $method,
                $reflectionProperty->getName()
            );

            static::assertTrue($dtoReflection->hasMethod($method), $message);
        }
    }

    /**
     * @throws Throwable
     */
    public function testThatSetterCallsSetVisitedMethod(): void
    {
        $dtoReflection = new ReflectionClass($this->dtoClass);
        $properties = $this->getDtoProperties();

        /**
         * @var MockObject|RestDtoInterface $mock
         */
        $mock = $this->getMockBuilder($this->dtoClass)
            ->onlyMethods(['setVisited'])
            ->getMock();

        $mock->expects(static::atLeast(count($properties)))
            ->method('setVisited');

        $expectedVisited = [];

        /** @var ReflectionProperty $reflectionProperty */
        foreach ($properties as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter method for current property
            $setter = 'set' . ucfirst($reflectionProperty->getName());

            // Call setter method
            $mock->{$setter}($value);
        }

        static::assertSame($expectedVisited, $mock->getVisited());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetterMethodReturnExpectedAfterSetter(): void
    {
        $dtoReflection = new ReflectionClass($this->dtoClass);

        $dto = new $this->dtoClass();

        foreach ($this->getDtoProperties() as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter and getter methods for current property
            $setter = 'set' . ucfirst($reflectionProperty->getName());
            $getter = 'get' . ucfirst($reflectionProperty->getName());

            // Call setter method
            $dto->{$setter}($value);

            static::assertSame($value, $dto->{$getter}());
        }
    }

    /**
     * @dataProvider dataProviderTestThatSetterOnlyAcceptSpecifiedType
     *
     * @throws Throwable
     *
     * @testdox Test that `setter` method for `$field` will fail if parameter is not `$type` type.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(string $field, string $type): void
    {
        // Get "valid" value for current property
        $value = PhpUnitUtil::getInvalidValueForType($type);

        $this->expectException(TypeError::class);

        $setter = 'set' . ucfirst($field);

        $dto = new $this->dtoClass();
        $dto->{$setter}($value);

        $message = sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            is_object($value) ? gettype($value) : '(' . gettype($value) . ')' . $value
        );

        static::fail($message);
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatSetterOnlyAcceptSpecifiedType(): array
    {
        $iterator = function (ReflectionProperty $reflectionProperty) {
            return [
                $reflectionProperty->getName(),
                $this->getType($this->dtoClass, $reflectionProperty->getName()),
            ];
        };

        return array_map($iterator, $this->getDtoProperties());
    }

    private static function initializePropertyExtractor(): void
    {
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

        self::$propertyInfo = new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors,
            $propertyInitializableExtractors
        );
    }

    /**
     * @return mixed
     *
     * @throws Throwable
     */
    private function getValueForProperty(ReflectionClass $dtoReflection, ReflectionProperty $reflectionProperty)
    {
        return PhpUnitUtil::getValidValueForType(
            $this->getType($dtoReflection->getName(), $reflectionProperty->getName())
        );
    }

    /**
     * @return ReflectionProperty[]
     *
     * @throws Throwable
     */
    private function getDtoProperties(): array
    {
        $dtoClass = $this->dtoClass;
        $dtoReflection = new ReflectionClass($dtoClass);
        $dto = new $dtoClass();

        $filter = fn (ReflectionProperty $reflectionProperty) =>
            !$reflectionProperty->isStatic()
            && !$reflectionProperty->isPrivate()
            && (
                $dtoClass === $reflectionProperty->getDeclaringClass()->getName()
                || $reflectionProperty->getDeclaringClass()->isInstance($dto)
            );

        /* @var ReflectionProperty[] $properties */
        return array_filter($dtoReflection->getProperties(), $filter);
    }

    private function getType(string $class, string $property): string
    {
        if (self::$propertyInfo === null) {
            self::initializePropertyExtractor();
        }

        $types = self::$propertyInfo->getTypes($class, $property);

        $type = $types[0]->getBuiltinType();

        if ($type === 'object') {
            $type = $types[0]->getClassName();
        }

        return $type;
    }
}
