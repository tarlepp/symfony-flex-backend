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
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use TypeError;
use function array_filter;
use function array_map;
use function count;
use function gettype;
use function is_object;
use function preg_match;
use function preg_replace;
use function preg_split;
use function sprintf;
use function strncmp;
use function trim;
use function ucfirst;

/**
 * Class DtoTestCase
 *
 * @package App\Tests\Integration\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class DtoTestCase extends KernelTestCase
{
    protected string $dtoClass;

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

        unset($dtoReflection);
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
            ->setMethods(['setVisited'])
            ->getMock();

        $mock->expects(static::exactly(count($properties)))
            ->method('setVisited');

        $expectedVisited = [];

        /** @var ReflectionProperty $reflectionProperty */
        foreach ($properties as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter method for current property
            $setter = 'set' . ucfirst($reflectionProperty->getName());

            // Call setter method
            $mock->$setter($value);
        }

        static::assertEquals($expectedVisited, $mock->getVisited());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetterMethodReturnExpectedAfterSetter(): void
    {
        $dtoReflection = new ReflectionClass($this->dtoClass);

        $dto = new $this->dtoClass();

        /** @var ReflectionProperty $reflectionProperty */
        foreach ($this->getDtoProperties() as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter and getter methods for current property
            $setter = 'set' . ucfirst($reflectionProperty->getName());
            $getter = 'get' . ucfirst($reflectionProperty->getName());

            // Call setter method
            $dto->$setter($value);

            static::assertSame($value, $dto->$getter());
        }
    }

    /**
     * @dataProvider dataProviderTestThatSetterOnlyAcceptSpecifiedType
     *
     * @param string $field
     * @param string $type
     *
     * @throws Throwable
     */
    public function testThatSetterOnlyAcceptSpecifiedType(string $field, string $type): void
    {
        // Get "valid" value for current property
        $value = PhpUnitUtil::getInvalidValueForType($type);

        $this->expectException(TypeError::class);

        $setter = 'set' . ucfirst($field);

        $dto = new $this->dtoClass();
        $dto->$setter($value);

        $message = sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            is_object($value) ? gettype($value) : '(' . gettype($value) . ')' . $value
        );

        static::fail($message);
    }

    /**
     * @return array
     *
     * @throws Throwable
     */
    public function dataProviderTestThatSetterOnlyAcceptSpecifiedType(): array
    {
        $iterator = function (ReflectionProperty $reflectionProperty) {
            return [
                $reflectionProperty->getName(),
                $this->parseType($reflectionProperty),
            ];
        };

        return array_map($iterator, $this->getDtoProperties());
    }

    /**
     * @param ReflectionClass    $dtoReflection
     * @param ReflectionProperty $reflectionProperty
     *
     * @return float|int|string
     *
     * @throws Throwable
     */
    private function getValueForProperty(ReflectionClass $dtoReflection, ReflectionProperty $reflectionProperty)
    {
        $type = $this->parseType($reflectionProperty);

        if ($type === null) {
            $message = sprintf(
                "DTO class '%s' property '%s' does not have required '@var' annotation",
                $dtoReflection->getName(),
                $reflectionProperty->getName()
            );

            throw new DomainException($message);
        }

        return PhpUnitUtil::getValidValueForType($type);
    }

    /**
     * @param ReflectionProperty $reflectionProperty
     *
     * @return null|string
     */
    private function parseType(ReflectionProperty $reflectionProperty): ?string
    {
        $output = null;

        foreach (preg_split("/(\r?\n)/", $reflectionProperty->getDocComment()) as $line) {
            // if starts with an asterisk
            if (preg_match('/^(?=\s+?\*[^\/])(.+)/', $line, $matches)) {
                $info = $matches[1];

                // remove wrapping whitespace
                $info = trim($info);

                // remove leading asterisk
                $info = preg_replace('/^(\*\s+?)/', '', $info);

                if (strncmp($info, '@', 1) === 0) {
                    // get the name of the param
                    preg_match('/@var (.*)/', $info, $matches);

                    if (!$matches) {
                        $message = sprintf(
                            'Cannot determine parameter type for "%s"',
                            $info
                        );

                        throw new InvalidArgumentException($message);
                    }

                    if ($matches[1]) {
                        $output = trim($matches[1]);

                        break;
                    }
                }
            }
        }

        return $output;
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

        $filter = static function (ReflectionProperty $reflectionProperty) use ($dto, $dtoClass) {
            return !$reflectionProperty->isStatic()
                && ($dtoClass === $reflectionProperty->getDeclaringClass()->getName()
                    || $reflectionProperty->getDeclaringClass()->isInstance($dto));
        };

        /** @var ReflectionProperty[] $properties */
        $properties = array_filter($dtoReflection->getProperties(), $filter);

        return $properties;
    }
}
