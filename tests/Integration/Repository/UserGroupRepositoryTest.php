<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\UserGroup;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\UserGroupRepository;
use App\Resource\UserGroupResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method UserGroupResource getResource()
 * @method UserGroupRepository getRepository()
 */
final class UserGroupRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = UserGroup::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = UserGroupRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = UserGroupResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'role',
        'users',
        'apiKeys',
        'createdBy',
        'updatedBy',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchColumns = [
        'role',
        'name',
    ];
}
