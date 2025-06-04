<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\User;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method UserResource getResource()
 * @method UserRepository getRepository()
 */
final class UserRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = User::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = UserRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = UserResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'createdBy',
        'updatedBy',
        'userGroups',
        'logsRequest',
        'logsLogin',
        'logsLoginFailure',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchColumns = [
        'username',
        'firstName',
        'lastName',
        'email',
    ];
}
