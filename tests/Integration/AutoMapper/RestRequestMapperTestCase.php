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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

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

    abstract protected function getRequestMapper(): RestRequestMapper;

    /**
     * @phpstan-return  MockObject&RestResourceInterface
     */
    abstract protected function getResource(): MockObject;

    /**
     * @dataProvider dataProviderTestThatMapToObjectReturnsExpectedDtoObject
     *
     * @param class-string $expectedDto
     *
     * @throws Throwable
     *
     * @testdox Test that `mapToObject` method returns `$expectedDto` DTO object
     */
    public function testThatMapToObjectReturnsExpectedDtoObject(string $expectedDto): void
    {
        self::assertInstanceOf(
            $expectedDto,
            $this->getRequestMapper()->mapToObject(new Request(), new $expectedDto()),
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
}
