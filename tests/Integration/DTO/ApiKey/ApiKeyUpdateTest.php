<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyUpdateTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKeyUpdate;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class ApiKeyUpdateTest
 *
 * @package App\Tests\Integration\DTO\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUpdateTest extends DtoTestCase
{
    protected $dtoClass = ApiKeyUpdate::class;
}
