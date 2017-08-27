<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/LogLoginRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\LogLogin;
use App\Repository\LogLoginRepository;

/**
 * Class LogLoginRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogLogin::class;

    /**
     * @var string
     */
    protected $repositoryName = LogLoginRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
    ];
}
