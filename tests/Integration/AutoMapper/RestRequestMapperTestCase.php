<?php

namespace App\Tests\Integration\AutoMapper;

use App\AutoMapper\RestRequestMapper;
use App\AutoMapper\User\RequestMapper;
use Generator;
use InvalidArgumentException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

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
     * @throws Throwable
     */
    public function testThatMapToObjectThrowsAnExceptionIfSourceIsNotRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RestRequestMapper expects that $source is Request object, "stdClass" provided');

        $this->mapperObject->mapToObject(new stdClass(), new stdClass());
    }

    /**
     * @throws Throwable
     */
    public function testThatMapToObjectThrowsAnExceptionIfDestinationIsNotRestDtoInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'RestRequestMapper expects that $destination is instance of RestDtoInterface object, "stdClass" provided'
        );

        $this->mapperObject->mapToObject(new Request(), new stdClass());
    }

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
