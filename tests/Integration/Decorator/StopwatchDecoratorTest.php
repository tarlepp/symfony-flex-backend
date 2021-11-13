<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Decorator/StopwatchDecoratorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Decorator;

use App\Decorator\StopwatchDecorator;
use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Resource\ApiKeyResource;
use App\Validator\Constraints\EntityReferenceExists;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Generator;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ProxyManager\Proxy\AccessInterceptorValueHolderInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

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

        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf($expected, $decorator->decorate($service));
    }

    /**
     * @testdox Test that decorator calls expected methods from `StopWatch` service
     */
    public function testThatDecoratorCallsStopWatchStartAndStopMethods(): void
    {
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        /** @noinspection ClassConstantCanBeUsedInspection */
        $stopWatch
            ->expects(self::once())
            ->method('start')
            ->with('EntityReferenceExists->getTargets', 'App\Validator\Constraints\EntityReferenceExists');

        $stopWatch
            ->expects(self::once())
            ->method('stop')
            ->with('EntityReferenceExists->getTargets');

        $decorator = new StopwatchDecorator(new AccessInterceptorValueHolderFactory(), $stopWatch);

        /** @var EntityReferenceExists $decoratedService */
        $decoratedService = $decorator->decorate(new EntityReferenceExists());

        self::assertSame('property', $decoratedService->getTargets());
    }

    /**
     * @testdox Test that `decorate` method returns exact same service if factory throws an exception
     */
    public function testThatDecoratorReturnsTheSameInstanceIfFactoryFails(): void
    {
        $service = new EntityReferenceExists();

        $factory = $this->getMockBuilder(AccessInterceptorValueHolderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $factory
            ->expects(self::once())
            ->method('createProxy')
            ->willThrowException(new Exception('foo'));

        $decorator = new StopwatchDecorator($factory, $stopWatch);

        self::assertSame($service, $decorator->decorate($service));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `decorate` method decorates possible inner objects / services
     */
    public function testThatDecoratorAlsoDecoratesInnerObjects(): void
    {
        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $stopWatch
            ->expects(self::exactly(2))
            ->method('start');

        $stopWatch
            ->expects(self::exactly(2))
            ->method('stop');

        $decorator = new StopwatchDecorator(new AccessInterceptorValueHolderFactory(), $stopWatch);
        $repository = new ApiKeyRepository($managerRegistry);
        $resource = new ApiKeyResource($repository);

        /** @var ApiKeyResource $decoratedService */
        $decoratedService = $decorator->decorate($resource);
        $decoratedService->getRepository()->getEntityManager();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `decorate` method does not decorate entity objects
     */
    public function testThatDecoratorDoesNotTryToDecorateEntityObjects(): void
    {
        $apiKey = new ApiKey();

        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('find')
            ->willReturn($apiKey);

        $stopWatch
            ->expects(self::once())
            ->method('start');

        $stopWatch
            ->expects(self::once())
            ->method('stop');

        $decorator = new StopwatchDecorator(new AccessInterceptorValueHolderFactory(), $stopWatch);
        $repository = new ApiKeyRepository($managerRegistry);

        /** @var ApiKeyRepository $decoratedService */
        $decoratedService = $decorator->decorate($repository);

        self::assertSame($apiKey, $decoratedService->find($apiKey->getId()));
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
