<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Helpers\RepositoryTestCase;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepositoryTest extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = User::class;

    /**
     * @var string
     */
    protected $repositoryName = UserRepository::class;

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
        'username',
        'firstname',
        'surname',
        'email',
    ];
}
