<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\Role;
use App\Repository\RoleRepository;

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
     * @var array
     */
    protected $associations = [
        'userGroups',
    ];

    /**
     * @var array
     */
    protected $searchColumns = [
        'id',
    ];
}
