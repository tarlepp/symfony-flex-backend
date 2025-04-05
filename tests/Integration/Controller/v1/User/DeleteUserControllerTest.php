<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/User/DeleteUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\User;

use App\Controller\v1\User\DeleteUserController;
use App\Entity\User;
use App\Resource\UserResource;
use App\Rest\ResponseHandler;
use App\Tests\Integration\TestCase\RestIntegrationControllerTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * @package App\Tests\Integration\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method DeleteUserController getController()
 */
final class DeleteUserControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = DeleteUserController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = UserResource::class;

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `__invoke($request, $user, $user)` method trows exception if user is trying to delete himself'
    )]
    public function testThatInvokeMethodThrowsAnExceptionIfUserTriesToDeleteHimself(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You cannot remove yourself...');

        $resource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $resource
            ->expects(self::never())
            ->method('delete');

        $request = Request::create('/');
        $user = new User();

        new DeleteUserController($resource)($request, $user, $user);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `__invoke($request, $requestUser, $loggedInUser)` method calls expected service methods')]
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $resource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();
        $responseHandler = $this->getMockBuilder(ResponseHandler::class)->disableOriginalConstructor()->getMock();

        $request = Request::create('/', 'DELETE');
        $requestUser = new User();
        $loggedInUser = new User();

        $resource
            ->expects($this->once())
            ->method('delete')
            ->with($requestUser->getId())
            ->willReturn($requestUser);

        $responseHandler
            ->expects($this->once())
            ->method('createResponse')
            ->with($request, $requestUser, $resource);

        new DeleteUserController($resource)
            ->setResponseHandler($responseHandler)
            ->__invoke($request, $requestUser, $loggedInUser);
    }
}
