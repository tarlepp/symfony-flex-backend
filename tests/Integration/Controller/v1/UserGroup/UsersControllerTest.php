<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/UserGroup/UsersControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\UserGroup;

use App\Controller\v1\UserGroup\UsersController;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use App\Rest\ResponseHandler;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * @package App\Tests\Integration\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UsersControllerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `__invoke(Request $request, UserGroup $userGroup)` method calls expected service methods')]
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();
        $responseHandler = $this->getMockBuilder(ResponseHandler::class)->disableOriginalConstructor()->getMock();

        $request = new Request();
        $userGroup = new UserGroup()->setRole(new Role('Some Role'));
        $user = new User()->addUserGroup($userGroup);

        $userResource
            ->expects($this->once())
            ->method('getUsersForGroup')
            ->with($userGroup)
            ->willReturn([$user]);

        $responseHandler
            ->expects($this->once())
            ->method('createResponse')
            ->with($request, [$user], $userResource);

        new UsersController($userResource, $responseHandler)($request, $userGroup);
    }
}
