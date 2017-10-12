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
use App\Resource\UserResource;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepositoryTest extends RepositoryTestCase
{
    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityName = User::class;

    /**
     * @var string
     */
    protected $repositoryName = UserRepository::class;

    /**
     * @var string
     */
    protected $resourceName = UserResource::class;

    /**
     * @var array
     */
    protected $associations = [
        'userGroups',
        'logsRequest',
        'logsLogin',
        'createdBy',
        'updatedBy',
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
