<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/HealthzResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use App\Resource\HealthzResource;

/**
 * Class HealthzResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzResourceTest extends ResourceTestCase
{
    protected string $entityClass = Healthz::class;
    protected string $repositoryClass = HealthzRepository::class;
    protected string $resourceClass = HealthzResource::class;
}
