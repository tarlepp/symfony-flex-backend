<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyCreateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKeyCreate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class ApiKeyCreateTest
 *
 * @package App\Tests\Integration\DTO\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyCreateTest extends DtoTestCase
{
    /**
     * @var class-string
     */
    protected string $dtoClass = ApiKeyCreate::class;
}
