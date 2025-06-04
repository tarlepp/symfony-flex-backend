<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/RoleResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role;
use App\Repository\BaseRepository;
use App\Repository\RoleRepository;
use App\Resource\RoleResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class RoleResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = Role::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = RoleRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = RoleResource::class;
}
