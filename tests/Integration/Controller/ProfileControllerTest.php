<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/ProfileControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\ProfileController;
use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\User;

/**
 * Class ProfileControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ProfileControllerTest extends KernelTestCase
{
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage Not supported user
     */
    public function testThatProfileActionThrowsAnExceptionIfTokenStorageContainsWrongUserInstance(): void
    {
        static::bootKernel();

        $serializer = static::$container->get('serializer');
        $rolesService = static::$container->get(RolesService::class);

        $user = new User('test_user', 'test_password');
        $token = new PreAuthenticatedToken($user, 'credentials', 'providerKey', [RolesService::ROLE_LOGGED]);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $controller = new ProfileController();
        $controller->profileAction($tokenStorage, $serializer, $rolesService);

        unset($controller, $tokenStorage, $user, $rolesService, $serializer);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage Not supported user
     */
    public function testThatGroupsActionThrowsAnExceptionIfTokenStorageContainsWrongUserInstance(): void
    {
        static::bootKernel();

        $serializer = static::$container->get('serializer');

        $user = new User('test_user', 'test_password');
        $token = new PreAuthenticatedToken($user, 'credentials', 'providerKey', [RolesService::ROLE_LOGGED]);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $controller = new ProfileController();
        $controller->groupsAction($tokenStorage, $serializer);

        unset($controller, $tokenStorage, $token, $user, $serializer);
    }
}
