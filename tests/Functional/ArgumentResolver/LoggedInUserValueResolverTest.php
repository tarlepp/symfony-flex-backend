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
use App\Repository\UserRepository;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
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
     * @throws Throwable
     *
     * @testdox Test that `resolve` method with `$username` input returns expected `User` object.
     */
    public function testThatResolveReturnsExpectedUserObject(string $username): void
    {
        static::bootKernel();

        /**
         * @var UserRepository $userRepository
         */
        $userRepository = static::$container->get(UserRepository::class);

        $user = $userRepository->loadUserByUsername($username, false);

        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $userTypeIdentification = new UserTypeIdentification($tokenStorage, $userRepository);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $userTypeIdentification);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        static::assertSame([$user], iterator_to_array($resolver->resolve(Request::create('/'), $metadata)));
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that integration with argument resolver with `$username` returns expected `User` object.
     */
    public function testThatIntegrationWithArgumentResolverReturnsExpectedUser(string $username): void
    {
        static::bootKernel();

        /**
         * @var UserRepository $userRepository
         */
        $userRepository = static::$container->get(UserRepository::class);

        $user = $userRepository->loadUserByUsername($username, false);

        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $userTypeIdentification = new UserTypeIdentification($tokenStorage, $userRepository);

        $argumentResolver = new ArgumentResolver(
            null,
            [new LoggedInUserValueResolver($tokenStorage, $userTypeIdentification)]
        );

        $closure = static function (User $loggedInUser): void {
            // Do nothing
        };

        static::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

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
