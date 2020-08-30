<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\UserGroup;
use App\Repository\UserGroupRepository;
use App\Resource\UserGroupResource;

/**
 * Class UserGroupRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupRepositoryTest extends RepositoryTestCase
{
    protected string $entityName = UserGroup::class;
    protected string $repositoryName = UserGroupRepository::class;
    protected string $resourceName = UserGroupResource::class;
    protected array $associations = [
        'role',
        'users',
        'apiKeys',
        'createdBy',
        'updatedBy',
    ];
    protected array $searchColumns = [
        'role',
        'name',
    ];
}
