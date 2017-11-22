<?php
declare(strict_types=1);
/**
 * /tests/Integration/Controller/AuthControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\AuthController;
use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\User;

/**
 * Class AuthControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthControllerTest extends KernelTestCase
{
    /**
     * @codingStandardsIgnoreStart
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage You need to send JSON body to obtain token eg. {"username":"username","password":"password"}
     *
     * @codingStandardsIgnoreEnd
     */
    public function testThatGetTokenThrowsAnException(): void
    {
        $controller = new AuthController();
        $controller->getTokenAction();
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage Not supported user
     */
    public function testThatProfileActionThrowsAnExceptionIfTokenStorageContainsWrongUserInstance(): void
    {
        self::bootKernel();

        $serializer = static::$kernel->getContainer()->get('serializer');
        $rolesService = static::$kernel->getContainer()->get(RolesService::class);

        $user = new User('test_user', 'test_password');
        $token = new PreAuthenticatedToken($user, 'credentials', 'providerKey', [RolesService::ROLE_LOGGED]);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $controller = new AuthController();
        $controller->profileAction($tokenStorage, $serializer, $rolesService);
    }
}
