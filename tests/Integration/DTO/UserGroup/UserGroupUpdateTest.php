<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/UserGroup/UserGroupUpdateTest.php
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupUpdate;
use App\Tests\Integration\TestCase\DtoTestCase;

final class UserGroupUpdateTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
     * @phpstan-var class-string<UserGroupUpdate>
     */
    protected static string $dtoClass = UserGroupUpdate::class;
}
