<?php
declare(strict_types = 1);

/**
 * /tests/Integration/ValueResolver/RestDtoValueResolverTest.php
 */

namespace App\Tests\Integration\ValueResolver;

use App\DTO\RestDtoInterface;
use App\Rest\Controller;
use App\Rest\ControllerCollection;
use App\ValueResolver\RestDtoValueResolver;
use AutoMapperPlus\AutoMapperInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

final class RestDtoValueResolverTest extends KernelTestCase
{
    /**
     * @psalm-param ControllerCollection<Controller>&MockObject $controllerCollection
     */
    #[DataProvider('dataProviderTestThatSupportMethodWorksAsExpected')]
    #[TestDox('Test that `supports` method returns expected result `$expected`')]
    public function testThatSupportMethodWorksAsExpected(
        bool $expected,
        mixed $controllerCollection,
        Request $request,
        ArgumentMetadata $argumentMetadata,
        string $method,
    ): void {
        $autoMapperStub = self::createStub(AutoMapperInterface::class);

        /** @psalm-suppress MixedMethodCall, MixedArgument */
        $controllerCollection
            ->expects($this->{$method}())
            ->method('has')
            ->willReturn($expected);

        $resolver = new RestDtoValueResolver($controllerCollection, $autoMapperStub);

        self::assertSame($expected, $resolver->supports($request, $argumentMetadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `resolve` method works as expected')]
    public function testThatResolveMethodWorksAsExpected(): void
    {
        $controllerCollectionMock = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $autoMapperMock = $this->getMockBuilder(AutoMapperInterface::class)->getMock();
        $controllerMock = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMock();
        $restDtoStub = self::createStub(RestDtoInterface::class);

        $resolver = new RestDtoValueResolver($controllerCollectionMock, $autoMapperMock);
        $metadata = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);
        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $resolver->supports($request, $metadata);

        $controllerCollectionMock
            ->expects($this->exactly(2))
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $controllerCollectionMock
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($controllerMock);

        $controllerMock
            ->expects($this->once())
            ->method('getDtoClass')
            ->with('createMethod')
            ->willReturn(RestDtoInterface::class);

        $autoMapperMock
            ->expects($this->once())
            ->method('map')
            ->with($request, RestDtoInterface::class)
            ->willReturn($restDtoStub);

        $resolver->supports($request, $metadata);

        self::assertSame($restDtoStub, $resolver->resolve($request, $metadata)->current());
    }

    /**
     * @return Generator<array-key, array<int, mixed>>
     */
    public static function dataProviderTestThatSupportMethodWorksAsExpected(): Generator
    {
        /** @psalm-suppress InternalMethod, MixedAssignment */
        $controllerCollectionMock = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $controllerCollectionMock,
            Request::create('/'),
            $argumentMetaData,
            'never',
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::bar',
        ]);

        yield [
            false,
            $controllerCollectionMock,
            $request,
            $argumentMetaData,
            'never',
        ];

        /** @psalm-suppress InternalMethod, MixedAssignment */
        $controllerCollectionMock = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            false,
            $controllerCollectionMock,
            $request,
            $argumentMetaData,
            'once',
        ];

        /** @psalm-suppress InternalMethod, MixedAssignment */
        $controllerCollectionMock = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $controllerCollectionMock,
            $request,
            $argumentMetaData,
            'never',
        ];

        /** @psalm-suppress InternalMethod, MixedAssignment */
        $controllerCollectionMock = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            true,
            $controllerCollectionMock,
            $request,
            $argumentMetaData,
            'once',
        ];
    }
}
