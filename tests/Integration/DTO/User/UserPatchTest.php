<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserPatchTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserPatch;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserPatchTest
 *
 * @package App\Tests\Integration\DTO\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserPatchTest extends DtoTestCase
{
    protected $dtoClass = UserPatch::class;
}
