<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ValueResolver/RestDtoValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

/**
 * @package App\Tests\Integration\ValueResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RestDtoValueResolverTest extends KernelTestCase
{
    #[DataProvider('dataProviderTestThatSupportMethodWorksAsExpected')]
    #[TestDox('Test that `supports` method returns expected result `$expected`')]
    public function testThatSupportMethodWorksAsExpected(
        bool $expected,
        mixed $controllerCollection,
        Request $request,
        ArgumentMetadata $argumentMetadata,
        string $method,
    ): void {
        $autoMapper = $this->getMockBuilder(AutoMapperInterface::class)->getMock();

        $controllerCollection
            ->expects($this->{$method}())
            ->method('has')
            ->with('foo')
            ->willReturn($expected);

        $resolver = new RestDtoValueResolver($controllerCollection, $autoMapper);

        self::assertSame($expected, $resolver->supports($request, $argumentMetadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `resolve` method works as expected')]
    public function testThatResolveMethodWorksAsExpected(): void
    {
        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $autoMapper = $this->getMockBuilder(AutoMapperInterface::class)->getMock();
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMock();
        $restDto = $this->getMockBuilder(RestDtoInterface::class)->getMock();

        $resolver = new RestDtoValueResolver($controllerCollection, $autoMapper);
        $metadata = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);
        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $resolver->supports($request, $metadata);

        $controllerCollection
            ->expects($this->exactly(2))
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $controllerCollection
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($controller);

        $controller
            ->expects($this->once())
            ->method('getDtoClass')
            ->with('createMethod')
            ->willReturn(RestDtoInterface::class);

        $autoMapper
            ->expects($this->once())
            ->method('map')
            ->with($request, RestDtoInterface::class)
            ->willReturn($restDto);

        $resolver->supports($request, $metadata);

        self::assertSame($restDto, $resolver->resolve($request, $metadata)->current());
    }

    public static function dataProviderTestThatSupportMethodWorksAsExpected(): Generator
    {
        /** @psalm-suppress InternalMethod */
        $controllerCollection = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $controllerCollection,
            Request::create('/'),
            $argumentMetaData,
            'never',
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::bar',
        ]);

        yield [
            false,
            $controllerCollection,
            $request,
            $argumentMetaData,
            'never',
        ];

        /** @psalm-suppress InternalMethod */
        $controllerCollection = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            false,
            $controllerCollection,
            $request,
            $argumentMetaData,
            'once',
        ];

        /** @psalm-suppress InternalMethod */
        $controllerCollection = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $controllerCollection,
            $request,
            $argumentMetaData,
            'never',
        ];

        /** @psalm-suppress InternalMethod */
        $controllerCollection = new MockBuilder(new self(self::class), ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            true,
            $controllerCollection,
            $request,
            $argumentMetaData,
            'once',
        ];
    }
}
