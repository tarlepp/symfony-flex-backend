<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/ResourceCollectionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\ApiKey;
use App\Entity\DateDimension;
use App\Entity\Healthz;
use App\Entity\LogLogin;
use App\Entity\LogLoginFailure;
use App\Entity\LogRequest;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\ApiKeyResource;
use App\Resource\DateDimensionResource;
use App\Resource\HealthzResource;
use App\Resource\LogLoginFailureResource;
use App\Resource\LogLoginResource;
use App\Resource\LogRequestResource;
use App\Resource\ResourceCollection;
use App\Resource\RoleResource;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use ArrayObject;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ResourceCollectionTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResourceCollectionTest extends KernelTestCase
{
    public function testThatGetMethodThrowsAnException(): void
    {
        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource \'FooBar\' does not exist');

        (new ResourceCollection($this->getEmptyIteratorAggregate(), $logger))
            ->get('FooBar');
    }

    public function testThatLoggerIsCalledIfGetMethodGetIteratorThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource \'FooBar\' does not exist');

        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $logger
            ->expects(static::once())
            ->method('error');

        (new ResourceCollection($this->getIteratorAggregateThatThrowsAnException(), $logger))
            ->get('FooBar');
    }

    public function testThatGetEntityResourceMethodThrowsAnException(): void
    {
        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource class does not exist for entity \'FooBar\'');

        (new ResourceCollection($this->getEmptyIteratorAggregate(), $logger))
            ->getEntityResource('FooBar');
    }

    public function testThatLoggerIsCalledIfGetEntityResourceMethodGetIteratorThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource class does not exist for entity \'FooBar\'');

        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $logger
            ->expects(static::once())
            ->method('error');

        (new ResourceCollection($this->getIteratorAggregateThatThrowsAnException(), $logger))
            ->getEntityResource('FooBar');
    }

    public function testThatGetAllReturnsCorrectCountOfResources(): void
    {
        static::assertCount(9, $this->getCollection()->getAll());
    }

    public function testThatCountMethodReturnsExpectedCount(): void
    {
        static::assertSame(9, $this->getCollection()->count(), 'REST resource count from collection was not expected');
    }

    /**
     * @dataProvider dataProviderTestThatGetReturnsExpectedResource
     *
     * @testdox Test that `get` method with `$resourceClass` input returns instance of that resource class.
     */
    public function testThatGetReturnsExpectedResource(string $resourceClass): void
    {
        static::assertInstanceOf($resourceClass, $this->getCollection()->get($resourceClass));
    }

    /**
     * @dataProvider dataProviderTestThatGetEntityResourceReturnsExpectedResource
     *
     * @testdox Test that `getEntityResource` method with `$entityClass` input returns `$resourceClass` class.
     */
    public function testThatGetEntityResourceReturnsExpectedResource(string $resourceClass, string $entityClass): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf($resourceClass, $this->getCollection()->getEntityResource($entityClass));
    }

    /**
     * @dataProvider dataProviderTestThatHasReturnsExpected
     *
     * @testdox Test that `has` method returns `$expected` with `$resource` input.
     */
    public function testThatHasReturnsExpected(bool $expected, ?string $resource): void
    {
        static::assertSame($expected, $this->getCollection()->has($resource));
    }

    /**
     * @dataProvider dataProviderTestThatHasEntityResourceReturnsExpected
     *
     * @testdox Test that `hasEntityResource` method returns `$expected` with `$entity` input.
     */
    public function testThatHasEntityResourceReturnsExpected(bool $expected, ?string $entity): void
    {
        static::assertSame($expected, $this->getCollection()->hasEntityResource($entity));
    }

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

    public function dataProviderTestThatGetEntityResourceReturnsExpectedResource(): Generator
    {
        yield [ApiKeyResource::class, ApiKey::class];
        yield [DateDimensionResource::class, DateDimension::class];
        yield [HealthzResource::class, Healthz::class];
        yield [LogLoginFailureResource::class, LogLoginFailure::class];
        yield [LogLoginResource::class, LogLogin::class];
        yield [LogRequestResource::class, LogRequest::class];
        yield [RoleResource::class, Role::class];
        yield [UserGroupResource::class, UserGroup::class];
        yield [UserResource::class, User::class];
    }

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
        yield [false, stdClass::class];
    }

    public function dataProviderTestThatHasEntityResourceReturnsExpected(): Generator
    {
        yield [true, ApiKey::class];
        yield [true, DateDimension::class];
        yield [true, Healthz::class];
        yield [true, LogLoginFailure::class];
        yield [true, LogLogin::class];
        yield [true, LogRequest::class];
        yield [true, Role::class];
        yield [true, UserGroup::class];
        yield [true, User::class];
        yield [false, null];
        yield [false, 'ResourceThatDoesNotExists'];
        yield [false, stdClass::class];
    }

    private function getCollection(): ResourceCollection
    {
        static::bootKernel();

        return static::$container->get(ResourceCollection::class);
    }

    private function getEmptyIteratorAggregate(): IteratorAggregate
    {
        return new class([]) implements IteratorAggregate {
            private ArrayObject $iterator;

            /**
             * Constructor of the class.
             *
             * @param $input
             */
            public function __construct($input)
            {
                $this->iterator = new ArrayObject($input);
            }

            public function getIterator(): ArrayObject
            {
                return $this->iterator;
            }
        };
    }

    private function getIteratorAggregateThatThrowsAnException(): IteratorAggregate
    {
        return new class() implements IteratorAggregate {
            public function getIterator(): ArrayObject
            {
                throw new LogicException('Exception with getIterator');
            }
        };
    }
}
