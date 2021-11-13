<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/RestRequestMapperConfigurationTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper;

use App\AutoMapper\RestAutoMapperConfiguration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RestRequestMapperConfigurationTestCase
 *
 * @package App\Tests\Integration\AutoMapper
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
     * @testdox Test that `AutoMapperConfiguration` instance is created
     */
    public function testThatInstanceCanBeCreated(): void
    {
        $requestMapper = $this->getMockBuilder($this->requestMapper)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertInstanceOf(RestAutoMapperConfiguration::class, new $this->autoMapperConfiguration($requestMapper));
    }
}
