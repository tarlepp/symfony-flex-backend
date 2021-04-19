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
use Exception;
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
        $decorator = new StopwatchDecorator(new AccessInterceptorValueHolderFactory(), new Stopwatch());

        static::assertInstanceOf($expected, $decorator->decorate($service));
    }

    /**
     * @testdox Test that decorator calls expected methods from `StopWatch` service
     */
    public function testThatDecoratorCallsStopWatchStartAndStopMethods(): void
    {
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatch
            ->expects(static::once())
            ->method('start')
            ->with('EntityReferenceExists->getTargets', 'App\Validator\Constraints\EntityReferenceExists');

        $stopWatch
            ->expects(static::once())
            ->method('stop')
            ->with('EntityReferenceExists->getTargets');

        $decorator = new StopwatchDecorator(new AccessInterceptorValueHolderFactory(), $stopWatch);

        /** @var EntityReferenceExists $decoratedService */
        $decoratedService = $decorator->decorate(new EntityReferenceExists());

        static::assertSame('property', $decoratedService->getTargets());
    }

    /**
     * @testdox Test that `decorate` method returns exact same service if factory throws an expection
     */
    public function testThatDecoratorReturnsTheSameInstanceIfFactoryFails(): void
    {
        $service = new EntityReferenceExists();

        $factory = $this->getMockBuilder(AccessInterceptorValueHolderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $factory
            ->expects(static::once())
            ->method('createProxy')
            ->willThrowException(new Exception('foo'));

        $decorator = new StopwatchDecorator($factory, $stopWatch);

        static::assertSame($service, $decorator->decorate($service));
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
