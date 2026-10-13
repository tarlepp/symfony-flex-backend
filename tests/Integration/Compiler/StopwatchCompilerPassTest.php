<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Compiler/StopwatchCompilerPassTest.php
 */

namespace App\Tests\Integration\Compiler;

use App\Compiler\StopwatchCompilerPass;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class StopwatchCompilerPassTest extends KernelTestCase
{
    #[TestDox('Test that `findTaggedServiceIds` method is called expected times')]
    public function testThatFindTaggedServiceIdsMethodIsCalled(): void
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

        $containerMock
            ->expects($this->exactly(5))
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        new StopwatchCompilerPass()
            ->process($containerMock);
    }

    #[TestDox('Test that no other container methods are called when tagged service is not supported')]
    public function testThatIfServiceStartsWithAppNoOtherContainerMethodsAreCalled(): void
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

        $containerMock
            ->expects($this->exactly(5))
            ->method('findTaggedServiceIds')
            ->willReturn([
                stdClass::class => [],
            ]);

        $containerMock
            ->expects($this->never())
            ->method('getDefinition');

        $containerMock
            ->expects($this->never())
            ->method('setDefinition');

        new StopwatchCompilerPass()
            ->process($containerMock);
    }

    public function testThatAllExpectedContainerMethodsAreCalled(): void
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
        $definitionStub = self::createStub(Definition::class);

        $containerMock
            ->expects($this->exactly(5))
            ->method('findTaggedServiceIds')
            ->willReturn([
                'App\Foo' => [],
            ]);

        $containerMock
            ->expects($this->exactly(5))
            ->method('getDefinition')
            ->with('App\Foo')
            ->willReturn($definitionStub);

        $containerMock
            ->expects($this->exactly(5))
            ->method('setDefinition')
            ->with('App\Foo.stopwatch');

        new StopwatchCompilerPass()
            ->process($containerMock);
    }
}
