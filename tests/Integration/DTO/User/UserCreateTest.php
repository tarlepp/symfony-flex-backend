<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/User/UserCreateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserCreate;
use App\Tests\Integration\TestCase\DtoTestCase;

/**
 * @package App\Tests\Integration\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserCreateTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<UserCreate>
     */
    protected static string $dtoClass = UserCreate::class;
}
