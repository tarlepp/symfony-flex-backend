<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/DateDimensionRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\DateDimension;
use App\Repository\DateDimensionRepository;
use App\Resource\DateDimensionResource;

/**
 * Class DateDimensionRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DateDimensionRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = DateDimension::class;

    /**
     * @var string
     */
    protected $repositoryName = DateDimensionRepository::class;

    /**
     * @var string
     */
    protected $resourceName = DateDimensionResource::class;
}
