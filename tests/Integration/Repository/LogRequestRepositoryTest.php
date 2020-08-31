<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/LogRequestRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\LogRequest;
use App\Repository\LogRequestRepository;
use App\Resource\LogRequestResource;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogRequestRepositoryTest extends RepositoryTestCase
{
    protected LogRequestRepository $repository;
    protected string $entityName = LogRequest::class;
    protected string $repositoryName = LogRequestRepository::class;
    protected string $resourceName = LogRequestResource::class;
    protected array $associations = [
        'user',
        'apiKey',
    ];
}
