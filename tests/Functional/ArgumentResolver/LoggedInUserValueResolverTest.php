<?php
declare(strict_types = 1);
/**
 * /tests/Functional/ArgumentResolver/LoggedInUserValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use function assert;
use function iterator_to_array;

/**
 * Class LoggedInUserValueResolverTest
 *
 * @package App\Tests\Functional\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoggedInUserValueResolverTest extends KernelTestCase
{
    private ?UserRepository $repository = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        assert(static::$container->get(UserRepository::class) instanceof UserRepository);

        $this->repository = static::$container->get(UserRepository::class);
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `resolve` method with `$username` input returns expected `User` object.
     */
    public function testThatResolveReturnsExpectedUserObject(string $username): void
    {
        $repository = $this->getRepository();

        $user = $repository->loadUserByUsername($username, false);

        static::assertNotNull($user);

        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $userTypeIdentification = new UserTypeIdentification($tokenStorage, $repository);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $userTypeIdentification);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);
        $request = Request::create('/');

        $resolver->supports($request, $metadata);

        static::assertSame([$user], iterator_to_array($resolver->resolve($request, $metadata)));
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
        $repository = $this->getRepository();

        $user = $repository->loadUserByUsername($username, false);

        static::assertNotNull($user);

        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $userTypeIdentification = new UserTypeIdentification($tokenStorage, $repository);

        $argumentResolver = new ArgumentResolver(
            null,
            [new LoggedInUserValueResolver($tokenStorage, $userTypeIdentification)],
        );

        $closure = static function (User $loggedInUser): void {
            // Do nothing
        };

        static::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @return Generator<array{0: string}>
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

    private function getRepository(): UserRepository
    {
        assert($this->repository instanceof UserRepository);

        return $this->repository;
    }
}
