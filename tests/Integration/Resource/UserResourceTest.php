<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/UserResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\User;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Security\RolesService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserResourceTest extends ResourceTestCase
{
    protected string $entityClass = User::class;
    protected string $repositoryClass = UserRepository::class;
    protected string $resourceClass = UserResource::class;

    protected function getResource(
        BaseRepositoryInterface $repository,
        ValidatorInterface $validator
    ): RestResourceInterface {
        $roles = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        return new $this->resourceClass($repository, $validator, $roles);
    }
}
