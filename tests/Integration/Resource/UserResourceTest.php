<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/UserResourceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\UserResource;

/**
 * Class UserResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserResourceTest extends ResourceTestCase
{
    protected $entityClass = User::class;
    protected $resourceClass = UserResource::class;
    protected $repositoryClass = UserRepository::class;
}
