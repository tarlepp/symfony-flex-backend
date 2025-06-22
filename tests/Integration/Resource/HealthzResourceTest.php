<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/HealthzResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Healthz;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\BaseRepository;
use App\Repository\HealthzRepository;
use App\Resource\HealthzResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class HealthzResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = Healthz::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = HealthzRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = HealthzResource::class;
}
