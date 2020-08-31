<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/RoleResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Resource\RoleResource;

/**
 * Class RoleResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleResourceTest extends ResourceTestCase
{
    protected string $entityClass = Role::class;
    protected string $repositoryClass = RoleRepository::class;
    protected string $resourceClass = RoleResource::class;
}
