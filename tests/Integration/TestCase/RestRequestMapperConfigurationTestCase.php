<?php
declare(strict_types = 1);

/**
 * /tests/Integration/TestCase/RestRequestMapperConfigurationTestCase.php
 */

namespace App\Tests\Integration\TestCase;

use App\AutoMapper\RestAutoMapperConfiguration;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use AutoMapperPlus\Configuration\MappingInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function count;

abstract class RestRequestMapperConfigurationTestCase extends KernelTestCase
{
    /**
     * @var class-string<RestAutoMapperConfiguration>
     */
    protected string $autoMapperConfiguration;

    /**
     * @var class-string
     */
    protected string $requestMapper;

    /**
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses;

    #[TestDox('Test that `AutoMapperConfiguration` instance is created')]
    public function testThatInstanceCanBeCreated(): void
    {
        $this->expectNotToPerformAssertions();

        $requestMapperStub = $this->createStub($this->requestMapper);

        /** @psalm-suppress UnsafeInstantiation */
        new $this->autoMapperConfiguration($requestMapperStub);
    }

    #[TestDox('Test that `AutoMapperConfiguration` instance is configured as expected')]
    public function testThatConfigureMethodIsCallingExpectedMethods(): void
    {
        $requestMapperStub = $this->createStub($this->requestMapper);

        $configMock = $this->getMockBuilder(AutoMapperConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mappingMock = $this->getMockBuilder(MappingInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock
            ->expects($this->exactly(count(static::$requestMapperClasses)))
            ->method('registerMapping')
            ->willReturn($mappingMock);

        $mappingMock
            ->expects($this->exactly(count(static::$requestMapperClasses)))
            ->method('useCustomMapper')
            ->with($requestMapperStub);

        /** @psalm-suppress UnsafeInstantiation */
        $mapper = new $this->autoMapperConfiguration($requestMapperStub);

        $mapper->configure($configMock);
    }
}
