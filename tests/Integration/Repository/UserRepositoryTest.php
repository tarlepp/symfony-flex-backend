<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\UserResource;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepositoryTest extends RepositoryTestCase
{
    protected UserRepository $repository;
    protected string $entityName = User::class;
    protected string $repositoryName = UserRepository::class;
    protected string $resourceName = UserResource::class;
    protected array $associations = [
        'createdBy',
        'updatedBy',
        'userGroups',
        'logsRequest',
        'logsLogin',
        'logsLoginFailure',
    ];
    protected array $searchColumns = [
        'username',
        'firstName',
        'lastName',
        'email',
    ];
}
