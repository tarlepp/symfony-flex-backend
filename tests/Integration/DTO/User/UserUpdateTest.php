<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserUpdateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserUpdate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserUpdateTest
 *
 * @package App\Tests\Integration\DTO\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserUpdateTest extends DtoTestCase
{
    protected $dtoClass = UserUpdate::class;
}
