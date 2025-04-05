<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/ApiKey/AutoMapperConfigurationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper\UserGroup;

use App\AutoMapper\UserGroup\AutoMapperConfiguration;
use App\AutoMapper\UserGroup\RequestMapper;
use App\DTO\UserGroup\UserGroupCreate;
use App\DTO\UserGroup\UserGroupPatch;
use App\DTO\UserGroup\UserGroupUpdate;
use App\Tests\Integration\TestCase\RestRequestMapperConfigurationTestCase;

/**
 * @package App\Tests\Integration\AutoMapper\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class AutoMapperConfigurationTest extends RestRequestMapperConfigurationTestCase
{
    /**
     * @var class-string
     */
    protected string $autoMapperConfiguration = AutoMapperConfiguration::class;

    /**
     * @var class-string
     */
    protected string $requestMapper = RequestMapper::class;

    /**
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        UserGroupCreate::class,
        UserGroupUpdate::class,
        UserGroupPatch::class,
    ];
}
