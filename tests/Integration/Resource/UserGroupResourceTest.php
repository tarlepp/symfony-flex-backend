<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/UserGroupResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\UserGroup;
use App\Repository\UserGroupRepository;
use App\Resource\UserGroupResource;

/**
 * Class UserGroupResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupResourceTest extends ResourceTestCase
{
    protected string $entityClass = UserGroup::class;
    protected string $repositoryClass = UserGroupRepository::class;
    protected string $resourceClass = UserGroupResource::class;
}
