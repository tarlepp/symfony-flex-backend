<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupUpdateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupUpdate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserGroupUpdateTest
 *
 * @package App\Tests\Integration\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupUpdateTest extends DtoTestCase
{
    protected string $dtoClass = UserGroupUpdate::class;
}
