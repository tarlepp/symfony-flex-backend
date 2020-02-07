<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/RestRequestMapperTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper;

use App\AutoMapper\RestRequestMapper;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class RestRequestMapperTestCase
 *
 * @package App\Tests\Integration\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property RestRequestMapper $mapperObject
 */
class RestRequestMapperTestCase extends KernelTestCase
{
    protected string $mapperClass;
    protected array $restDtoClasses = [];

    /**
     * @dataProvider dataProviderTestThatMapToObjectReturnsExpectedDtoObject
     *
     * @param string $expectedDto
     *
     * @throws Throwable
     */
    public function testThatMapToObjectReturnsExpectedDtoObject(string $expectedDto):  void
    {
        static::assertInstanceOf($expectedDto, $this->mapperObject->mapToObject(new Request(), new $expectedDto()));
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatMapToObjectReturnsExpectedDtoObject(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }
}
