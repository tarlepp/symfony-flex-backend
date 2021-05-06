<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Compiler/StopwatchCompilerPassTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Compiler;

use App\Compiler\StopwatchCompilerPass;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class StopwatchCompilerPassTest
 *
 * @package App\Tests\Compiler\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class StopwatchCompilerPassTest extends KernelTestCase
{
    /**
     * @testdox Test that `findTaggedServiceIds` method is called expected times
     */
    public function testThatFindTaggedServiceIdsMethodIsCalled(): void
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

        $container
            ->expects(static::exactly(5))
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        (new StopwatchCompilerPass())->process($container);
    }

    /**
     * @testdox Test that no other container methods are called when tagged service is not supported
     */
    public function testThatIfServiceStartsWithAppNoOtherContainerMethodsAreCalled(): void
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

        $container
            ->expects(static::exactly(5))
            ->method('findTaggedServiceIds')
            ->willReturn([stdClass::class => []]);

        $container
            ->expects(static::never())
            ->method('getDefinition');

        $container
            ->expects(static::never())
            ->method('setDefinition');

        (new StopwatchCompilerPass())->process($container);
    }

    public function testThatAllExpectedContainerMethodsAreCalled(): void
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
        $definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();

        $container
            ->expects(static::exactly(5))
            ->method('findTaggedServiceIds')
            ->willReturn(['App\Foo' => []]);

        $container
            ->expects(static::exactly(5))
            ->method('getDefinition')
            ->with('App\Foo')
            ->willReturn($definition);

        $container
            ->expects(static::exactly(5))
            ->method('setDefinition')
            ->with('App\Foo.stopwatch');

        (new StopwatchCompilerPass())->process($container);
    }
}
