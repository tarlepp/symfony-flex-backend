<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupCreateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupCreate;
use App\Tests\Integration\TestCase\DtoTestCase;

/**
 * @package App\Tests\Integration\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserGroupCreateTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<UserGroupCreate>
     */
    protected static string $dtoClass = UserGroupCreate::class;
}
