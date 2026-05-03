<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupPatchTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupPatch;
use App\Tests\Integration\TestCase\DtoTestCase;

/**
 * @package App\Tests\Integration\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserGroupPatchTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<UserGroupPatch>
     */
    protected static string $dtoClass = UserGroupPatch::class;
}
