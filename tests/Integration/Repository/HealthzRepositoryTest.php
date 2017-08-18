<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;

/**
 * Class HealthzRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = Healthz::class;

    /**
     * @var string
     */
    protected $repositoryName = HealthzRepository::class;
}
