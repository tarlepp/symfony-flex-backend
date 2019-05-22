<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Resource\RoleResource;

/**
 * Class RoleRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = Role::class;

    /**
     * @var string
     */
    protected $repositoryName = RoleRepository::class;

    /**
     * @var string
     */
    protected $resourceName = RoleResource::class;

    /**
     * @var array
     */
    protected $associations = [
        'userGroups',
        'createdBy',
        'updatedBy',
    ];
}
