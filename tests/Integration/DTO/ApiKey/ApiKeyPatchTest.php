<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyPatchTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKeyPatch;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class ApiKeyPatchTest
 *
 * @package App\Tests\Integration\DTO\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyPatchTest extends DtoTestCase
{
    protected $dtoClass = ApiKeyPatch::class;
}
