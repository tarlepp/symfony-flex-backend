<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/ApiKeyResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\ApiKey;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\ApiKeyRepository;
use App\Repository\BaseRepository;
use App\Resource\ApiKeyResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class ApiKeyResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = ApiKey::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = ApiKeyRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = ApiKeyResource::class;
}
