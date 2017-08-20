<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/LogLoginSuccessRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\LogLoginSuccess;
use App\Repository\LogLoginSuccessRepository;

/**
 * Class LogLoginSuccessRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginSuccessRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogLoginSuccess::class;

    /**
     * @var string
     */
    protected $repositoryName = LogLoginSuccessRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
    ];
}
