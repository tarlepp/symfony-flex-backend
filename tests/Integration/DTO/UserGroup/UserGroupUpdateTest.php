<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/UserGroup/UserGroupUpdateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\UserGroup;

use App\DTO\UserGroup\UserGroupUpdate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserGroupUpdateTest
 *
 * @package App\Tests\Integration\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupUpdateTest extends DtoTestCase
{
    /**
     * @var class-string
     */
    protected string $dtoClass = UserGroupUpdate::class;
}
