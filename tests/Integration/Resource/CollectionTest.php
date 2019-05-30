<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/CollectionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Resource\ApiKeyResource;
use App\Resource\Collection;
use App\Resource\DateDimensionResource;
use App\Resource\HealthzResource;
use App\Resource\LogLoginFailureResource;
use App\Resource\LogLoginResource;
use App\Resource\LogRequestResource;
use App\Resource\RoleResource;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\Tests\Fixtures\TraversableArrayObject;

/**
 * Class CollectionTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CollectionTest extends KernelTestCase
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Resource 'FooBar' does not exists
     */
    public function testThatGetMethodThrowsAnException(): void
    {
        $collection = new Collection(new TraversableArrayObject());
        $collection->get('FooBar');

        unset($collection);
    }

    public function testThatGetAllReturnsCorrectCountOfResources(): void
    {
        $collection = $this->getCollection();

        static::assertCount(9, $collection->getAll());
    }

    /**
     * @dataProvider dataProviderTestThatGetReturnsExpectedResource
     *
     * @param string $resourceClass
     */
    public function testThatGetReturnsExpectedResource(string $resourceClass): void
    {
        $collection = $this->getCollection();

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf($resourceClass, $collection->get($resourceClass));
    }

    /**
     * @dataProvider dataProviderTestThatHasReturnsExpected
     *
     * @param bool        $expected
     * @param string|null $resource
     */
    public function testThatHasReturnsExpected(bool $expected, ?string $resource): void
    {
        $collection = $this->getCollection();

        static::assertSame($expected, $collection->has($resource));
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetReturnsExpectedResource(): Generator
    {
        yield [ApiKeyResource::class];
        yield [DateDimensionResource::class];
        yield [HealthzResource::class];
        yield [LogLoginFailureResource::class];
        yield [LogLoginResource::class];
        yield [LogRequestResource::class];
        yield [RoleResource::class];
        yield [UserGroupResource::class];
        yield [UserResource::class];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatHasReturnsExpected(): Generator
    {
        yield [true, ApiKeyResource::class];
        yield [true, DateDimensionResource::class];
        yield [true, HealthzResource::class];
        yield [true, LogLoginFailureResource::class];
        yield [true, LogLoginResource::class];
        yield [true, LogRequestResource::class];
        yield [true, RoleResource::class];
        yield [true, UserGroupResource::class];
        yield [true, UserResource::class];
        yield [false, null];
        yield [false, 'ResourceThatDoesNotExists'];
    }

    /**
     * @return Collection
     */
    private function getCollection(): Collection
    {
        static::bootKernel();

        return static::$container->get(Collection::class);
    }
}
