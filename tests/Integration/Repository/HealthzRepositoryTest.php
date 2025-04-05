<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/HealthzRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Healthz;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\HealthzRepository;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Resource\HealthzResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method HealthzResource getResource()
 * @method HealthzRepository getRepository()
 */
final class HealthzRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = Healthz::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = HealthzRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = HealthzResource::class;
}
