<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/LogLoginFailureRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\LogLoginFailure;
use App\Repository\LogLoginFailureRepository;

/**
 * Class LogLoginFailureRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailureRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogLoginFailure::class;

    /**
     * @var string
     */
    protected $repositoryName = LogLoginFailureRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
    ];
}
