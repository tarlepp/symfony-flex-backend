<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\ApiKey;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\ApiKeyRepository;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Resource\ApiKeyResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method ApiKeyResource getResource()
 * @method ApiKeyRepository getRepository()
 */
final class ApiKeyRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = ApiKey::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = ApiKeyRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = ApiKeyResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'userGroups',
        'logsRequest',
        'createdBy',
        'updatedBy',
    ];
}
