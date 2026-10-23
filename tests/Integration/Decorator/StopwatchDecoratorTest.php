<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Decorator/StopwatchDecoratorTest.php
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
        $stopWatchMock = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatchMock
            ->expects($this->once())
            ->method('start')
            ->with('EntityReferenceExists->getTargets', EntityReferenceExists::class);

        $stopWatchMock
            ->expects($this->once())
            ->method('stop')
            ->with('EntityReferenceExists->getTargets');

        $decorator = new StopwatchDecorator($stopWatchMock, new NullLogger());

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

        $stopWatchStub = self::createStub(Stopwatch::class);

        $decorator = new StopwatchDecorator($stopWatchStub, new NullLogger());

        $result = $decorator->decorate($service);

        // Since the class has final methods, it should return the same instance
        self::assertSame('test', $result->someMethod());
    }

    #[TestDox('Test that `decorate` method returns service as-is when class itself is final')]
    public function testThatDecoratorReturnsTheSameInstanceForFinalClass(): void
    {
        $service = new FinalTestService();

        $stopWatchStub = self::createStub(Stopwatch::class);

        $decorator = new StopwatchDecorator($stopWatchStub, new NullLogger());

        $result = $decorator->decorate($service);

        // Since the class is final, it should return the same instance
        self::assertSame($service, $result);
        self::assertSame('final-test', $result->testMethod());
    }

    #[TestDox('Test that decorator handles parameters with internal class default values')]
    public function testThatDecoratorHandlesInternalClassDefaultValues(): void
    {
        $service = new ServiceWithInternalDefaults();

        $stopWatchMock = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatchMock
            ->expects($this->once())
            ->method('start');

        $stopWatchMock
            ->expects($this->once())
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatchMock, new NullLogger());

        $result = $decorator->decorate($service);

        // The decorated service should work with methods that have internal class default values
        self::assertSame('test', $result->methodWithInternalDefault());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `decorate` method decorates possible inner objects / services')]
    public function testThatDecoratorAlsoDecoratesInnerObjects(): void
    {
        $managerRegistryMock = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $entityManagerStub = self::createStub(EntityManager::class);
        $stopWatchMock = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $managerRegistryMock
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManagerStub);

        $entityManagerStub
            ->method('isOpen')
            ->willReturn(true);

        $stopWatchMock
            ->expects($this->exactly(2))
            ->method('start');

        $stopWatchMock
            ->expects($this->exactly(2))
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatchMock, new NullLogger());
        $repository = new ApiKeyRepository($managerRegistryMock);
        $resource = new ApiKeyResource($repository);

        /** @var ApiKeyResource $decoratedService */
        $decoratedService = $decorator->decorate($resource);
        $decoratedService->getRepository()
            ->getEntityManager();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `decorate` method does not decorate entity objects')]
    public function testThatDecoratorDoesNotTryToDecorateEntityObjects(): void
    {
        $apiKey = new ApiKey();

        $managerRegistryMock = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $entityManagerMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $stopWatchMock = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $managerRegistryMock
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManagerMock);

        $entityManagerMock
            ->method('isOpen')
            ->willReturn(true);

        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->willReturn($apiKey);

        $stopWatchMock
            ->expects($this->once())
            ->method('start');

        $stopWatchMock
            ->expects($this->once())
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatchMock, new NullLogger());
        $repository = new ApiKeyRepository($managerRegistryMock);

        /** @var ApiKeyRepository $decoratedService */
        $decoratedService = $decorator->decorate($repository);

        self::assertSame($apiKey, $decoratedService->find($apiKey->getId()));
    }

    #[TestDox('Test that decorator handles fluent interfaces (methods returning $this)')]
    public function testThatDecoratorHandlesFluentInterfaces(): void
    {
        $service = new FluentService();

        $stopWatchMock = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatchMock
            ->expects($this->atLeastOnce())
            ->method('start');

        $stopWatchMock
            ->expects($this->atLeastOnce())
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatchMock, new NullLogger());

        /** @var FluentService $decoratedService */
        $decoratedService = $decorator->decorate($service);

        $result = $decoratedService->setValue('test');

        // For fluent interfaces the proxy returns itself, not the wrapped instance
        self::assertSame($decoratedService, $result);
        self::assertSame('test', $decoratedService->getValue());
    }

    #[TestDox('Test that decorator correctly handles methods with `never` return type')]
    public function testThatDecoratorHandlesNeverReturnTypeMethods(): void
    {
        $service = new ServiceWithNeverReturnType();

        $stopWatchMock = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();

        $stopWatchMock
            ->expects($this->once())
            ->method('start');

        // `never`-returning methods throw before returning, so `stop` is never called
        $stopWatchMock
            ->expects($this->never())
            ->method('stop');

        $decorator = new StopwatchDecorator($stopWatchMock, new NullLogger());

        $result = $decorator->decorate($service);

        // The service should be successfully proxied (not returned as-is)
        self::assertNotSame($service, $result);

        // The proxied method still throws as expected
        $this->expectException(\RuntimeException::class);
        $result->alwaysThrows();
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
