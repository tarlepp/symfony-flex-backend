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
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\NullLogger;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

/**
 * @package App\Tests\Decorator\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class StopwatchDecoratorTest extends KernelTestCase
{
    /**
     * @param class-string $expected
     */
    #[DataProvider('dataProviderTestThatDecorateMethodReturnsExpected')]
    #[TestDox('Test that `decorate` method returns `$expected` when using `$service` instance as an input')]
    public function testThatDecorateMethodReturnsExpected(string $expected, object $service): void
    {
        $decorator = new StopwatchDecorator(new Stopwatch(), new NullLogger());

        self::assertInstanceOf($expected, $decorator->decorate($service));
    }

    #[TestDox('Test that decorator calls expected methods from `StopWatch` service')]
    public function testThatDecoratorCallsStopWatchStartAndStopMethods(): void
    {
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatch
            ->expects($this->once())
            ->method('start')
            ->with('EntityReferenceExists->getTargets', EntityReferenceExists::class);

        $stopWatch
            ->expects($this->once())
            ->method('stop')
            ->with('EntityReferenceExists->getTargets');

        $decorator = new StopwatchDecorator($stopWatch, new NullLogger());

        $decoratedService = $decorator->decorate(new EntityReferenceExists());

        self::assertSame('property', $decoratedService->getTargets());
    }

    #[TestDox('Test that `decorate` method returns service as-is if it cannot be proxied (e.g., final class)')]
    public function testThatDecoratorReturnsTheSameInstanceIfCannotBeProxied(): void
    {
        $service = new class() {
            final public function someMethod(): string
            {
                return 'test';
            }
        };

        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $decorator = new StopwatchDecorator($stopWatch, new NullLogger());

        $result = $decorator->decorate($service);

        // Since the class has final methods, it should return the same instance
        self::assertSame('test', $result->someMethod());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `decorate` method decorates possible inner objects / services')]
    public function testThatDecoratorAlsoDecoratesInnerObjects(): void
    {
        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $stopWatch
            ->expects($this->exactly(2))
            ->method('start');

        $stopWatch
            ->expects($this->exactly(2))
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatch, new NullLogger());
        $repository = new ApiKeyRepository($managerRegistry);
        $resource = new ApiKeyResource($repository);

        /** @var ApiKeyResource $decoratedService */
        $decoratedService = $decorator->decorate($resource);
        $decoratedService->getRepository()->getEntityManager();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `decorate` method does not decorate entity objects')]
    public function testThatDecoratorDoesNotTryToDecorateEntityObjects(): void
    {
        $apiKey = new ApiKey();

        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $stopWatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager
            ->expects($this->once())
            ->method('find')
            ->willReturn($apiKey);

        $stopWatch
            ->expects($this->once())
            ->method('start');

        $stopWatch
            ->expects($this->once())
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatch, new NullLogger());
        $repository = new ApiKeyRepository($managerRegistry);

        /** @var ApiKeyRepository $decoratedService */
        $decoratedService = $decorator->decorate($repository);

        self::assertSame($apiKey, $decoratedService->find($apiKey->getId()));
    }

    /**
     * @return Generator<array-key, array{0: class-string, 1: object}>
     */
    public static function dataProviderTestThatDecorateMethodReturnsExpected(): Generator
    {
        yield [EntityReferenceExists::class, new EntityReferenceExists()];
        yield [stdClass::class, new stdClass()];
    }
}
