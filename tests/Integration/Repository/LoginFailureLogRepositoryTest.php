<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/LoginFailureLogRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\LoginFailureLog;
use App\Repository\LoginFailureLogRepository;

/**
 * Class LoginFailureLogRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginFailureLogRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = LoginFailureLog::class;

    /**
     * @var string
     */
    protected $repositoryName = LoginFailureLogRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
    ];
}
