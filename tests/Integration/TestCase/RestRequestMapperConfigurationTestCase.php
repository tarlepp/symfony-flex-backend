<?php
declare(strict_types = 1);
/**
 * /tests/Integration/TestCase/RestRequestMapperConfigurationTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\TestCase;

use App\AutoMapper\RestAutoMapperConfiguration;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use AutoMapperPlus\Configuration\MappingInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function count;

/**
 * @package App\Tests\Integration\TestCase
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class RestRequestMapperConfigurationTestCase extends KernelTestCase
{
    /**
     * @var class-string
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
        $requestMapper = $this->getMockBuilder($this->requestMapper)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertInstanceOf(RestAutoMapperConfiguration::class, new $this->autoMapperConfiguration($requestMapper));
    }

    #[TestDox('Test that `AutoMapperConfiguration` instance is configured as expected')]
    public function testThatConfigureMethodIsCallingExpectedMethods(): void
    {
        $requestMapper = $this->getMockBuilder($this->requestMapper)
            ->disableOriginalConstructor()
            ->getMock();

        $config = $this->getMockBuilder(AutoMapperConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mapping = $this->getMockBuilder(MappingInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config
            ->expects($this->exactly(count(static::$requestMapperClasses)))
            ->method('registerMapping')
            ->willReturn($mapping);

        $mapping
            ->expects($this->exactly(count(static::$requestMapperClasses)))
            ->method('useCustomMapper')
            ->with($requestMapper);

        $mapper = new $this->autoMapperConfiguration($requestMapper);

        self::assertInstanceOf(AutoMapperConfiguratorInterface::class, $mapper);

        $mapper->configure($config);
    }
}
