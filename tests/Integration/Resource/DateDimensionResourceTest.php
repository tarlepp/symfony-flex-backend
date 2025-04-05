<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/DateDimensionResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\DateDimension;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\BaseRepository;
use App\Repository\DateDimensionRepository;
use App\Resource\DateDimensionResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class DateDimensionResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = DateDimension::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = DateDimensionRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = DateDimensionResource::class;
}
