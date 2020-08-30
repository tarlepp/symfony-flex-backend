<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/ApiKeyResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Resource\ApiKeyResource;

/**
 * Class ApiKeyResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyResourceTest extends ResourceTestCase
{
    protected string $entityClass = ApiKey::class;
    protected string $resourceClass = ApiKeyResource::class;
    protected string $repositoryClass = ApiKeyRepository::class;
}
