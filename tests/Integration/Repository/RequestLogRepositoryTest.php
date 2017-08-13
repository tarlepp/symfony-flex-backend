<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/RequestLogRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\RequestLog;
use App\Repository\RequestLogRepository;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLogRepositoryTest extends RepositoryTestCase
{
    /**
     * @var RequestLogRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityName = RequestLog::class;

    /**
     * @var string
     */
    protected $repositoryName = RequestLogRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'user',
    ];
}
