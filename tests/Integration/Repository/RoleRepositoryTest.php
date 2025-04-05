<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\RoleRepository;
use App\Resource\RoleResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method RoleResource getResource()
 * @method RoleRepository getRepository()
 */
final class RoleRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = Role::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = RoleRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = RoleResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'userGroups',
        'createdBy',
        'updatedBy',
    ];
}
