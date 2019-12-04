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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResourceCollectionTest extends KernelTestCase
{
    public function testThatGetMethodThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource \'FooBar\' does not exists');

        $iteratorAggregate = new class([]) implements IteratorAggregate {
            private ArrayObject $iterator;

            /**
             * Constructor  of the class.
             *
             * @param $input
             */
            public function __construct($input)
            {
                $this->iterator = new ArrayObject($input);
            }

            /**
             * @inheritDoc
             */
            public function getIterator(): ArrayObject
            {
                return $this->iterator;
            }
        };

        $collection = new ResourceCollection($iteratorAggregate);
        $collection->get('FooBar');

        unset($collection);
    }

    public function testThatLoggerIsCalledIfGetMethodGetIteratorThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource \'FooBar\' does not exists');

        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('error');

        $iteratorAggregate = new class() implements IteratorAggregate {
            /**
             * @inheritDoc
             */
            public function getIterator(): ArrayObject
            {
                throw new LogicException('Exception with getIterator');
            }
        };

        $collection = new ResourceCollection($iteratorAggregate);
        $collection->setLogger($logger);
        $collection->get('FooBar');
    }

    public function testThatGetEntityResourceMethodThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource class does not exists for entity \'FooBar\'');

        $iteratorAggregate = new class([]) implements IteratorAggregate {
            private ArrayObject $iterator;

            /**
             * Constructor  of the class.
             *
             * @param $input
             */
            public function __construct($input)
            {
                $this->iterator = new ArrayObject($input);
            }

            /**
             * @inheritDoc
             */
            public function getIterator(): ArrayObject
            {
                return $this->iterator;
            }
        };

        $collection = new ResourceCollection($iteratorAggregate);
        $collection->getEntityResource('FooBar');

        unset($collection);
    }

    public function testThatLoggerIsCalledIfGetEntityResourceMethodGetIteratorThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource class does not exists for entity \'FooBar\'');

        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('error');

        $iteratorAggregate = new class() implements IteratorAggregate {
            /**
             * @inheritDoc
             */
            public function getIterator(): ArrayObject
            {
                throw new LogicException('Exception with getIterator');
            }
        };

        $collection = new ResourceCollection($iteratorAggregate);
        $collection->setLogger($logger);
        $collection->getEntityResource('FooBar');
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
     * @param string $resourceClass
     */
    public function testThatGetReturnsExpectedResource(string $resourceClass): void
    {
        static::assertInstanceOf($resourceClass, $this->getCollection()->get($resourceClass));
    }

    /**
     * @dataProvider dataProviderTestThatGetEntityResourceReturnsExpectedResource
     *
     * @param string $entityClass
     * @param string $resourceClass
     */
    public function testThatGetEntityResourceReturnsExpectedResource(string $entityClass, string $resourceClass): void
    {
        static::assertInstanceOf($resourceClass, $this->getCollection()->getEntityResource($entityClass));
    }

    /**
     * @dataProvider dataProviderTestThatHasReturnsExpected
     *
     * @param bool        $expected
     * @param string|null $resource
     */
    public function testThatHasReturnsExpected(bool $expected, ?string $resource): void
    {
        static::assertSame($expected, $this->getCollection()->has($resource));
    }

    /**
     * @dataProvider dataProviderTestThatHasEntityResourceReturnsExpected
     *
     * @param bool        $expected
     * @param string|null $resource
     */
    public function testThatHasEntityResourceReturnsExpected(bool $expected, ?string $resource): void
    {
        static::assertSame($expected, $this->getCollection()->hasEntityResource($resource));
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
    public function dataProviderTestThatGetEntityResourceReturnsExpectedResource(): Generator
    {
        yield [ApiKey::class, ApiKeyResource::class];
        yield [DateDimension::class, DateDimensionResource::class];
        yield [Healthz::class, HealthzResource::class];
        yield [LogLoginFailure::class, LogLoginFailureResource::class];
        yield [LogLogin::class, LogLoginResource::class];
        yield [LogRequest::class, LogRequestResource::class];
        yield [Role::class, RoleResource::class];
        yield [UserGroup::class, UserGroupResource::class];
        yield [User::class, UserResource::class];
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
        yield [false, stdClass::class];
    }

    /**
     * @return Generator
     */
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

    /**
     * @return ResourceCollection
     */
    private function getCollection(): ResourceCollection
    {
        static::bootKernel();

        return static::$container->get(ResourceCollection::class);
    }
}
