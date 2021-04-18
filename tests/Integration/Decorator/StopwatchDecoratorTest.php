<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Decorator/StopwatchDecoratorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Decorator;

use App\Decorator\StopwatchDecorator;
use App\Validator\Constraints\EntityReferenceExists;
use Generator;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ProxyManager\Proxy\AccessInterceptorValueHolderInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class StopwatchDecoratorTest
 *
 * @package App\Tests\Decorator\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class StopwatchDecoratorTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatDecorateMethodReturnsExpected
     *
     * @param class-string $expected
     *
     * @testdox Test that `decorate` method returns `$expected` when using `$service` instance as an input
     */
    public function testThatDecorateMethodReturnsExpected(string $expected, object $service): void
    {
        $factory = new AccessInterceptorValueHolderFactory();
        $stopWatch = new Stopwatch();

        $decorator = new StopwatchDecorator($factory, $stopWatch);

        static::assertInstanceOf($expected, $decorator->decorate($service));
    }

    public function testThatDecoratorCallsStopWatchStartAndStopMethods(): void
    {
        $service = new EntityReferenceExists();

        $factory = new AccessInterceptorValueHolderFactory();
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatch
            ->expects(static::once())
            ->method('start')
            ->with('EntityReferenceExists->getTargets', 'App\Validator\Constraints\EntityReferenceExists');

        $decorator = new StopwatchDecorator($factory, $stopWatch);

        /** @var EntityReferenceExists $decoratedService */
        $decoratedService = $decorator->decorate($service);

        static::assertSame('property', $decoratedService->getTargets());
    }

    /**
     * @return Generator<array{0: string, 1: object}>
     */
    public function dataProviderTestThatDecorateMethodReturnsExpected(): Generator
    {
        yield [AccessInterceptorValueHolderInterface::class, new EntityReferenceExists()];
        yield [stdClass::class, new stdClass()];
    }
}
