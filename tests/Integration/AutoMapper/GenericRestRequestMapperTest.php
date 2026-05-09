<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/GenericRestRequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper;

use App\AutoMapper\ApiKey\RequestMapper;
use App\DTO\RestDtoInterface;
use App\Resource\UserGroupResource;
use App\Tests\Integration\AutoMapper\src\TestRestRequestMapper;
use App\Tests\Integration\AutoMapper\src\TestRestRequestMapperDto;
use App\Tests\Integration\AutoMapper\src\TestRestRequestMapperWithoutProperties;
use InvalidArgumentException;
use LengthException;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * @package App\Tests\Integration\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class GenericRestRequestMapperTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `mapToObject` method throws an exception if `source` is an array')]
    public function testThatMapToObjectThrowsAnExceptionIfSourceIsAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RestRequestMapper expects that $source is Request object, "array" provided');

        $resource = $this->getMockBuilder(UserGroupResource::class)->disableOriginalConstructor()->getMock();

        new RequestMapper($resource)->mapToObject([], new stdClass());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `mapToObject` method throws an exception if `source` is not `Request` object')]
    public function testThatMapToObjectThrowsAnExceptionIfSourceIsNotRequestObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RestRequestMapper expects that $source is Request object, "stdClass" provided');

        $resource = $this->getMockBuilder(UserGroupResource::class)->disableOriginalConstructor()->getMock();

        new RequestMapper($resource)->mapToObject(new stdClass(), new stdClass());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `mapToObject` throws an exception if `destination` is not instance of `RestDtoInterface`')]
    public function testThatMapToObjectThrowsAnExceptionIfDestinationIsNotRestDtoInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'RestRequestMapper expects that $destination is instance of RestDtoInterface object, "stdClass" provided'
        );

        $resource = $this->getMockBuilder(UserGroupResource::class)->disableOriginalConstructor()->getMock();

        new RequestMapper($resource)->mapToObject(new Request(), new stdClass());
    }

    /**
     * @throws Throwable
     */
    public function testThatMapToObjectThrowsAnExceptionIfThereIsNotPropertiesToConvert(): void
    {
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            'RestRequestMapper expects that mapper "' . TestRestRequestMapperWithoutProperties::class .
            '::$properties" contains properties to convert'
        );

        $mockRestDtoInterface = $this->getMockBuilder(RestDtoInterface::class)->getMock();

        new TestRestRequestMapperWithoutProperties()->mapToObject(new Request(), $mockRestDtoInterface);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `mapToObject` method works as expected')]
    public function testThatMapToObjectWorksAsExpected(): void
    {
        $request = new Request([], [
            'someProperty' => 'someValue',
            'someTransformProperty' => 'someTransformValue',
        ]);

        /** @var TestRestRequestMapperDto $transformedObject */
        $transformedObject = new TestRestRequestMapper()->mapToObject($request, new TestRestRequestMapperDto());

        self::assertSame('someValue', $transformedObject->getSomeProperty());
        self::assertSame('fbzrGenafsbezInyhr', $transformedObject->getSomeTransformProperty());
    }
}
