<?php
declare(strict_types = 1);
/**
 * /tests/Functional/ArgumentResolver/UserValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Functional\ArgumentResolver;

use App\ArgumentResolver\LoggedInUserValueResolver;
use App\Entity\User;
use App\Resource\UserResource;
use App\Security\SecurityUser;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Throwable;
use function iterator_to_array;

/**
 * Class UserValueResolverTest
 *
 * @package App\Tests\Functional\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedInUserValueResolverTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     *
     * @throws Throwable
     *
     * @testdox Test that `resolve` method with `$username` input returns expected `User` object.
     */
    public function testThatResolveReturnsExpectedUserObject(string $username): void
    {
        static::bootKernel();

        /** @var UserResource $resource */
        $resource = static::$container->get(UserResource::class);

        $user = $resource->findOneBy(['username' => $username]);

        $SecurityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($SecurityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $resource);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        static::assertSame([$user], iterator_to_array($resolver->resolve(Request::create('/'), $metadata)));
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     *
     * @throws Throwable
     *
     * @testdox Test that integration with argument resolver with `$username` returns expected `User` object.
     */
    public function testThatIntegrationWithArgumentResolverReturnsExpectedUser(string $username): void
    {
        static::bootKernel();

        /** @var UserResource $resource */
        $resource = static::$container->get(UserResource::class);

        $user = $resource->findOneBy(['username' => $username]);

        $SecurityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($SecurityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $argumentResolver = new ArgumentResolver(null, [new LoggedInUserValueResolver($tokenStorage, $resource)]);

        $closure = static function (User $loggedInUser) {
            // Do nothing
        };

        static::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @return Generator
     */
    public function dataProviderValidUsers(): Generator
    {
        yield ['john'];
        yield ['john-api'];
        yield ['john-logged'];
        yield ['john-user'];
        yield ['john-admin'];
        yield ['john-root'];
    }
}
