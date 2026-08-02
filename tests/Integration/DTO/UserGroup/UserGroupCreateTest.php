<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/UserGroup/UserGroupCreateTest.php
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupCreate;
use App\Tests\Integration\TestCase\DtoTestCase;

final class UserGroupCreateTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
     * @phpstan-var class-string<UserGroupCreate>
     */
    protected static string $dtoClass = UserGroupCreate::class;
}
