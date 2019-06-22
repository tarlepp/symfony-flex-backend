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
use InvalidArgumentException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class RestRequestMapperTestCase
 *
 * @package App\Tests\Integration\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestRequestMapperTestCase extends KernelTestCase
{
    /**
     * @var string
     */
    protected $mapperClass;

    /**
     * @var RestRequestMapper
     */
    protected $mapperObject;

    /**
     * @var string[]
     */
    protected $restDtoClasses = [];

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
