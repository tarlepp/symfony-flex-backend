<?php
declare(strict_types=1);
/**
 * /tests/Integration/DTO/DtoTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\DTO;

use App\DTO\RestDtoInterface;
use App\Utils\Tests\PHPUnitUtil;
use Psr\Log\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class DtoTestCase
 *
 * @package App\Tests\Integration\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DtoTestCase extends KernelTestCase
{
    /**
     * @var string
     */
    protected $dtoClass;

    public function testThatPropertiesHaveGetters(): void
    {
        $dtoReflection = new \ReflectionClass($this->dtoClass);

        foreach ($dtoReflection->getProperties() as $reflectionProperty) {
            if ($this->dtoClass !== $reflectionProperty->getDeclaringClass()->getName()) {
                continue;
            }

            $method = 'get' . \ucfirst($reflectionProperty->getName());

            $message = \sprintf(
                "REST DTO class '%s' does not have required getter method '%s' for property '%s'.",
                $this->dtoClass,
                $method,
                $reflectionProperty->getName()
            );

            self::assertTrue($dtoReflection->hasMethod($method), $message);
        }
    }

    public function testThatPropertiesHaveSetters(): void
    {
        $dtoReflection = new \ReflectionClass($this->dtoClass);

        foreach ($dtoReflection->getProperties() as $reflectionProperty) {
            if ($this->dtoClass !== $reflectionProperty->getDeclaringClass()->getName()) {
                continue;
            }

            $method = 'set' . \ucfirst($reflectionProperty->getName());

            $message = \sprintf(
                "REST DTO class '%s' does not have required setter method '%s' for property '%s'.",
                $this->dtoClass,
                $method,
                $reflectionProperty->getName()
            );

            self::assertTrue($dtoReflection->hasMethod($method), $message);
        }
    }

    public function testThatSetterCallsSetVisitedMethod(): void
    {
        $dtoClass = $this->dtoClass;
        $dtoReflection = new \ReflectionClass($this->dtoClass);

        $filter = function (\ReflectionProperty $reflectionProperty) use ($dtoClass) {
            return $dtoClass === $reflectionProperty->getDeclaringClass()->getName();
        };

        $properties = \array_filter($dtoReflection->getProperties(), $filter);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RestDtoInterface $mock
         */
        $mock = $this->getMockBuilder($dtoClass)
            ->setMethods(['setVisited'])
            ->getMock();

        $mock->expects(static::exactly(\count($properties)))
            ->method('setVisited');

        $expectedVisited = [];

        /** @var \ReflectionProperty $reflectionProperty */
        foreach ($properties as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter method for current property
            $setter = 'set' . \ucfirst($reflectionProperty->getName());

            // Call setter method
            $mock->$setter($value);
        }

        self::assertEquals($expectedVisited, $mock->getVisited());
    }

    public function testThatGetterMethodReturnExpectedAfterSetter(): void
    {
        $dtoClass = $this->dtoClass;
        $dtoReflection = new \ReflectionClass($this->dtoClass);

        $filter = function (\ReflectionProperty $reflectionProperty) use ($dtoClass) {
            return $dtoClass === $reflectionProperty->getDeclaringClass()->getName();
        };

        $properties = \array_filter($dtoReflection->getProperties(), $filter);

        $dto = new $dtoClass();

        /** @var \ReflectionProperty $reflectionProperty */
        foreach ($properties as $reflectionProperty) {
            // Get "valid" value for current property
            $value = $this->getValueForProperty($dtoReflection, $reflectionProperty);

            // Determine setter and getter methods for current property
            $setter = 'set' . \ucfirst($reflectionProperty->getName());
            $getter = 'get' . \ucfirst($reflectionProperty->getName());

            // Call setter method
            $dto->$setter($value);

            self::assertSame($value, $dto->$getter());
        }
    }

    /**
     * @dataProvider dataProviderTestThatSetterOnlyAcceptSpecifiedType
     * 
     * @param string $field
     * @param string $type
     */
    public function testThatSetterOnlyAcceptSpecifiedType(string $field, string $type): void
    {
        // Get "valid" value for current property
        $value = PHPUnitUtil::getInvalidValueForType($type);

        $this->expectException(\TypeError::class);

        $setter = 'set' . \ucfirst($field);

        $dto = new $this->dtoClass();
        $dto->$setter($value);

        $message = \sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            \is_object($value) ? \gettype($value) : '(' . \gettype($value) . ')' . $value
        );

        static::fail($message);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSetterOnlyAcceptSpecifiedType(): array
    {
        $dtoClass = $this->dtoClass;
        $dtoReflection = new \ReflectionClass($this->dtoClass);

        $filter = function (\ReflectionProperty $reflectionProperty) use ($dtoClass) {
            return $dtoClass === $reflectionProperty->getDeclaringClass()->getName();
        };

        $iterator = function (\ReflectionProperty $reflectionProperty) {
            return [
                $reflectionProperty->getName(),
                $this->parseType($reflectionProperty),
            ];
        };

        return \array_map($iterator, \array_filter($dtoReflection->getProperties(), $filter));
    }

    /**
     * @param \ReflectionClass    $dtoReflection
     * @param \ReflectionProperty $reflectionProperty
     *
     * @return float|int|string
     */
    private function getValueForProperty(\ReflectionClass $dtoReflection, \ReflectionProperty $reflectionProperty)
    {
        $type = $this->parseType($reflectionProperty);

        if ($type === null) {
            $message = \sprintf(
                "DTO class '%s' property '%s' does not have required '@var' annotation",
                $dtoReflection->getName(),
                $reflectionProperty->getName()
            );

            throw new \DomainException($message);
        }

        return PHPUnitUtil::getValidValueForType($type);
    }

    /**
     * @param \ReflectionProperty $reflectionProperty
     *
     * @return null|string
     */
    private function parseType(\ReflectionProperty $reflectionProperty): ?string
    {
        $output = null;

        foreach (\preg_split("/(\r?\n)/", $reflectionProperty->getDocComment()) as $line) {
            // if starts with an asterisk
            if (\preg_match('/^(?=\s+?\*[^\/])(.+)/', $line, $matches)) {
                $info = $matches[1];

                // remove wrapping whitespace
                $info = \trim($info);

                // remove leading asterisk
                $info = \preg_replace('/^(\*\s+?)/', '', $info);

                if ($info[0] === '@') {
                    // get the name of the param
                    \preg_match('/@var (.*)/', $info, $matches);

                    if (!$matches) {
                        $message = \sprintf(
                            'Cannot determine parameter type for "%s"',
                            $info
                        );

                        throw new InvalidArgumentException($message);
                    }

                    if ($matches[1]) {
                        $output = \trim($matches[1]);

                        break;
                    }
                }
            }
        }

        return $output;
    }
}
