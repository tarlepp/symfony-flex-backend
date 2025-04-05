<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/DateDimensionRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\DateDimension;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\DateDimensionRepository;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Resource\DateDimensionResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method DateDimensionResource getResource()
 * @method DateDimensionRepository getRepository()
 */
final class DateDimensionRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = DateDimension::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = DateDimensionRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = DateDimensionResource::class;
}
