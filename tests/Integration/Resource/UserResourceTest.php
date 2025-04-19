<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/UserResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\BaseRepository;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\RestResource;
use App\Security\RolesService;
use App\Tests\Integration\TestCase\ResourceTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;

/**
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

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getUsersForGroup(UserGroup $userGroup)` method calls expected service methods')]
    public function testThatGetUsersForGroupMethodCallsExpectedServiceMethods(): void
    {
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $userGroup = new UserGroup()->setRole(new Role('Some Role'));
        $user = new User()->addUserGroup($userGroup);

        $repository
            ->expects($this->once())
            ->method('findByAdvanced')
            ->with()
            ->willReturn([$user]);

        $rolesService
            ->expects($this->once())
            ->method('getInheritedRoles')
            ->with(['Some Role'])
            ->willReturn(['Some Role']);

        self::assertSame([$user], new UserResource($repository, $rolesService)->getUsersForGroup($userGroup));
    }
}
