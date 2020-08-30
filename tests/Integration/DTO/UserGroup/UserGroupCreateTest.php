<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupCreateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupCreate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserGroupCreateTest
 *
 * @package App\Tests\Integration\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupCreateTest extends DtoTestCase
{
    protected string $dtoClass = UserGroupCreate::class;
}
