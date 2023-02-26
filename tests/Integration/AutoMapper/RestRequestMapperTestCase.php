<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/RestRequestMapperTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper;

use App\AutoMapper\RestRequestMapper;
use App\Rest\Interfaces\RestResourceInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function class_exists;

/**
 * Class RestRequestMapperTestCase
 *
 * @package App\Tests\Integration\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class RestRequestMapperTestCase extends KernelTestCase
{
    protected ?RestRequestMapper $mapperObject = null;

    /**
     * @var array<int, class-string>
     */
    protected static array $restDtoClasses = [];

    /**
     * @param class-string $expectedDto
     *
     * @throws Throwable
     *
     * @testdox Test that `map` method returns `$expectedDto` DTO object
     */
    #[DataProvider('dataProviderTestThatMapMethodWorksAsExpected')]
    public function testThatMapMethodWorksAsExpected(string $expectedDto): void
    {
        self::assertInstanceOf(
            $expectedDto,
            $this->getRequestMapper()->map(new Request(), $expectedDto),
        );
    }

    /**
     * @param class-string $expectedDto
     *
     * @throws Throwable
     *
     * @testdox Test that `mapToObject` method returns `$expectedDto` DTO object
     */
    #[DataProvider('dataProviderTestThatMapToObjectReturnsExpectedDtoObject')]
    public function testThatMapToObjectReturnsExpectedDtoObject(string $expectedDto): void
    {
        self::assertTrue(class_exists($expectedDto));

        self::assertInstanceOf(
            $expectedDto,
            $this->getRequestMapper()->mapToObject(new Request(), new $expectedDto()),
        );
    }

    /**
     * @return Generator<array{0: class-string}>
     */
    public static function dataProviderTestThatMapMethodWorksAsExpected(): Generator
    {
        foreach (static::$restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }

    /**
     * @return Generator<array{0: class-string}>
     */
    public static function dataProviderTestThatMapToObjectReturnsExpectedDtoObject(): Generator
    {
        return self::dataProviderTestThatMapMethodWorksAsExpected();
    }

    abstract protected function getRequestMapper(): RestRequestMapper;

    /**
     * @phpstan-return  MockObject&RestResourceInterface
     */
    abstract protected function getResource(): MockObject;
}
