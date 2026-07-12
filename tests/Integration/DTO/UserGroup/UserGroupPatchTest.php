<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/UserGroup/UserGroupPatchTest.php
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupPatch;
use App\Tests\Integration\TestCase\DtoTestCase;

final class UserGroupPatchTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
     * @phpstan-var class-string<UserGroupPatch>
     */
    protected static string $dtoClass = UserGroupPatch::class;
}
