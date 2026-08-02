<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/ApiKey/ApiKeyCreateTest.php
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKeyCreate;
use App\Tests\Integration\TestCase\DtoTestCase;

final class ApiKeyCreateTest extends DtoTestCase
{
    /**
     * @psalm-var class-string<\App\DTO\RestDtoInterface>
     * @phpstan-var class-string<ApiKeyCreate>
     */
    protected static string $dtoClass = ApiKeyCreate::class;
}
