<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupPatchTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupPatch;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserGroupPatchTest
 *
 * @package App\Tests\Integration\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupPatchTest extends DtoTestCase
{
    protected string $dtoClass = UserGroupPatch::class;
}
