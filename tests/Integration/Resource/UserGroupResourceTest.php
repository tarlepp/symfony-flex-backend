<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/UserGroupResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\UserGroup;
use App\Repository\BaseRepository;
use App\Repository\UserGroupRepository;
use App\Resource\UserGroupResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserGroupResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = UserGroup::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = UserGroupRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = UserGroupResource::class;
}
