<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/User/UserCreateTest.php
 */

namespace App\Tests\Integration\DTO\User;

use App\DTO\User\UserCreate;
use App\Tests\Integration\TestCase\DtoTestCase;

final class UserCreateTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
     * @phpstan-var class-string<UserCreate>
     */
    protected static string $dtoClass = UserCreate::class;
}
