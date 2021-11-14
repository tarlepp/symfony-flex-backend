<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/ApiKey/AutoMapperConfigurationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper\ApiKey;

use App\AutoMapper\ApiKey\AutoMapperConfiguration;
use App\AutoMapper\ApiKey\RequestMapper;
use App\Tests\Integration\AutoMapper\RestRequestMapperConfigurationTestCase;

/**
 * Class AutoMapperConfigurationTest
 *
 * @package App\Tests\Integration\AutoMapper\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AutoMapperConfigurationTest extends RestRequestMapperConfigurationTestCase
{
    /**
     * @var class-string
     */
    protected string $autoMapperConfiguration = AutoMapperConfiguration::class;

    /**
     * @var class-string
     */
    protected string $requestMapper = RequestMapper::class;
}
