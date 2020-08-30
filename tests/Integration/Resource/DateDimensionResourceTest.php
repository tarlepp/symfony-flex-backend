<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/DateDimensionResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\DateDimension;
use App\Repository\DateDimensionRepository;
use App\Resource\DateDimensionResource;

/**
 * Class DateDimensionResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DateDimensionResourceTest extends ResourceTestCase
{
    protected string $entityClass = DateDimension::class;
    protected string $resourceClass = DateDimensionResource::class;
    protected string $repositoryClass = DateDimensionRepository::class;
}
