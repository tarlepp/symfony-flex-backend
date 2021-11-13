<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/RestRequestMapperTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper;

use App\AutoMapper\RestRequestMapper;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnexpectedValueException;

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
    protected array $restDtoClasses = [];

    /**
     * @dataProvider dataProviderTestThatMapToObjectReturnsExpectedDtoObject
     *
     * @param class-string $expectedDto
     *
     * @throws Throwable
     *
     * @testdox Test that `mapToObject` method returns `$expectedDto`
     */
    public function testThatMapToObjectReturnsExpectedDtoObject(string $expectedDto): void
    {
        self::assertInstanceOf(
            $expectedDto,
            $this->getMapperObject()->mapToObject(new Request(), new $expectedDto()),
        );
    }

    /**
     * @return Generator<array{0: class-string}>
     */
    public function dataProviderTestThatMapToObjectReturnsExpectedDtoObject(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }

    protected function getMapperObject(): RestRequestMapper
    {
        return $this->mapperObject ?? throw new UnexpectedValueException('MapperObject not set');
    }
}
