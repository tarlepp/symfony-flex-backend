<?php
declare(strict_types = 1);

/**
 * /tests/Integration/AutoMapper/ApiKey/AutoMapperConfigurationTest.php
 */

namespace App\Tests\Integration\AutoMapper\User;

use App\AutoMapper\RestAutoMapperConfiguration;
use App\AutoMapper\User\AutoMapperConfiguration;
use App\AutoMapper\User\RequestMapper;
use App\DTO\User\UserCreate;
use App\DTO\User\UserPatch;
use App\DTO\User\UserUpdate;
use App\Tests\Integration\TestCase\RestRequestMapperConfigurationTestCase;

final class AutoMapperConfigurationTest extends RestRequestMapperConfigurationTestCase
{
    /**
     * @var class-string<RestAutoMapperConfiguration>
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
        UserCreate::class,
        UserUpdate::class,
        UserPatch::class,
    ];
}
