<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\UserGroupRepository;
use App\Tests\Helpers\RepositoryTestCase;

/**
 * Class UserGroupRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = UserGroup::class;

    /**
     * @var string
     */
    protected $repositoryName = UserGroupRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'role',
        'users',
    ];

    /**
     * @var array
     */
    protected $searchColumns = [
        'role',
        'name',
    ];
}
