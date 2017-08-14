<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/LoginLogRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\LoginLog;
use App\Repository\LoginLogRepository;

/**
 * Class LoginLogRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLogRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = LoginLog::class;

    /**
     * @var string
     */
    protected $repositoryName = LoginLogRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
    ];
}
