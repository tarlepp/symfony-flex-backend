<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/UserResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\User;
use App\Repository\BaseRepository;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\RestResource;

/**
 * Class UserResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = User::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = UserRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = UserResource::class;
}
