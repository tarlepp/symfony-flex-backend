<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserCreateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserCreate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class UserCreateTest
 *
 * @package App\Tests\Integration\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserCreateTest extends DtoTestCase
{
    protected string $dtoClass = UserCreate::class;
}
