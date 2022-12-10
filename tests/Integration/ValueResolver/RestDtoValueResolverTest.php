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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

/**
 * Class RestDtoValueResolverTest
 *
 * @package App\Tests\Integration\ValueResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RestDtoValueResolverTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatSupportMethodWorksAsExpected
     *
     * @testdox Test that `supports` method returns expected result `$expected`
     */
    public function testThatSupportMethodWorksAsExpected(
        bool $expected,
        ControllerCollection $controllerCollection,
        Request $request,
        ArgumentMetadata $argumentMetadata
    ): void {
        $autoMapper = $this->getMockBuilder(AutoMapperInterface::class)->getMock();

        $resolver = new RestDtoValueResolver($controllerCollection, $autoMapper);

        self::assertSame($expected, $resolver->supports($request, $argumentMetadata));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `resolve` method works as expected
     */
    public function testThatResolveMethodWorksAsExpected(): void
    {
        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $autoMapper = $this->getMockBuilder(AutoMapperInterface::class)->getMock();
        $controller = $this->getMockBuilder(Controller::class)->getMock();
        $restDto = $this->getMockBuilder(RestDtoInterface::class)->getMock();

        $resolver = new RestDtoValueResolver($controllerCollection, $autoMapper);
        $metadata = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);
        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $resolver->supports($request, $metadata);

        $dto = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $controllerCollection
            ->expects(self::exactly(2))
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $controllerCollection
            ->expects(self::once())
            ->method('get')
            ->with('foo')
            ->willReturn($controller);

        $controller
            ->expects(self::once())
            ->method('getDtoClass')
            ->with('createMethod')
            ->willReturn(RestDtoInterface::class);

        $autoMapper
            ->expects(self::once())
            ->method('map')
            ->with($request, RestDtoInterface::class)
            ->willReturn($restDto);

        $resolver->supports($request, $metadata);
        static::assertSame($restDto, $resolver->resolve($request, $metadata)->current());
    }

    /**
     * @phpstan-return Generator<array{0: bool, 1: ControllerCollection, 2: Request, 3: ArgumentMetadata}>
     */
    public function dataProviderTestThatSupportMethodWorksAsExpected(): Generator
    {
        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $controllerCollection,
            Request::create('/'),
            $argumentMetaData,
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::bar',
        ]);

        yield [
            false,
            $controllerCollection,
            $request,
            $argumentMetaData,
        ];

        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $controllerCollection
            ->expects(self::once())
            ->method('has')
            ->with('foo')
            ->willReturn(false);

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            false,
            $controllerCollection,
            $request,
            $argumentMetaData,
        ];

        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $controllerCollection
            ->expects(self::never())
            ->method('has')
            ->with('foo');

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $controllerCollection,
            $request,
            $argumentMetaData,
        ];

        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $controllerCollection
            ->expects(self::once())
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            true,
            $controllerCollection,
            $request,
            $argumentMetaData,
        ];
    }
}
