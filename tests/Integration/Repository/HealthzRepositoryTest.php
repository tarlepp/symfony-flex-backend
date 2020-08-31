<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/HealthzRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use App\Resource\HealthzResource;

/**
 * Class HealthzRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzRepositoryTest extends RepositoryTestCase
{
    protected string $entityName = Healthz::class;
    protected string $repositoryName = HealthzRepository::class;
    protected string $resourceName = HealthzResource::class;
}
