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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

final class RestDtoValueResolverTest extends KernelTestCase
{
    #[DataProvider('dataProviderTestThatSupportMethodWorksAsExpected')]
    #[TestDox('Test that `supports` method returns expected result `$expected`')]
    public function testThatSupportMethodWorksAsExpected(
        bool $expected,
        Request $request,
        ArgumentMetadata $argumentMetadata,
        int $expectedCalls,
    ): void {
        /** @var ControllerCollection<Controller>&MockObject $controllerCollection */
        $controllerCollection = $this->createMock(ControllerCollection::class);
        $autoMapperStub = self::createStub(AutoMapperInterface::class);

        $controllerCollection
            ->expects($this->exactly($expectedCalls))
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
        /** @var ControllerCollection<Controller>&MockObject $controllerCollectionMock */
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
     * @return Generator<int, array{
     *     0: bool,
     *     1: Request,
     *     2: ArgumentMetadata,
     *     3: int,
     * }>
     */
    public static function dataProviderTestThatSupportMethodWorksAsExpected(): Generator
    {
        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            Request::create('/'),
            $argumentMetaData,
            0,
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::bar',
        ]);

        yield [
            false,
            $request,
            $argumentMetaData,
            0,
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            false,
            $request,
            $argumentMetaData,
            1,
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', null, false, false, null);

        yield [
            false,
            $request,
            $argumentMetaData,
            0,
        ];

        $request = new Request(attributes: [
            '_controller' => 'foo::createAction',
        ]);

        $argumentMetaData = new ArgumentMetadata('foo', RestDtoInterface::class, false, false, null);

        yield [
            true,
            $request,
            $argumentMetaData,
            1,
        ];
    }
}
