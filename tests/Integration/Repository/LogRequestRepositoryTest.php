<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/LogRequestRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\LogRequest;
use App\Repository\LogRequestRepository;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogRequestRepositoryTest extends RepositoryTestCase
{
    /**
     * @var LogRequestRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityName = LogRequest::class;

    /**
     * @var string
     */
    protected $repositoryName = LogRequestRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
        'apiKey',
    ];
}
