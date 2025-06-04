<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/LogRequestRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogRequest;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\LogRequestRepository;
use App\Resource\LogRequestResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method LogRequestResource getResource()
 * @method LogRequestRepository getRepository()
 */
final class LogRequestRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = LogRequest::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = LogRequestRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = LogRequestResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'user',
        'apiKey',
    ];
}
