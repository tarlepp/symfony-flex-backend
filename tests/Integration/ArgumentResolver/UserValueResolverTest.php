<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ArgumentResolver/UserValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\ArgumentResolver;

use App\ArgumentResolver\UserValueResolver;
use App\Entity\User;
use App\Resource\UserResource;
use App\Security\SecurityUser;
use Closure;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Throwable;
use function iterator_to_array;

/**
 * Class UserValueResolverTest
 *
 * @package App\Tests\Integration\ArgumentResolver
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserValueResolverTest extends KernelTestCase
{
    public function testThatSupportsReturnFalseWithWrongType(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $tokenStorage = new TokenStorage();
        $resolver = new UserValueResolver($tokenStorage, $userResource);
        $metadata = new ArgumentMetadata('foo', null, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    public function testThatSupportsReturnFalseWithNoToken(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $tokenStorage = new TokenStorage();
        $resolver = new UserValueResolver($tokenStorage, $userResource);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException
     * @expectedExceptionMessage JWT Token not found
     */
    public function testThatSupportsThrowsAnExceptionWithNonSecurityUser(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $token = new UsernamePasswordToken('username', 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new UserValueResolver($tokenStorage, $userResource);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        $resolver->supports(Request::create('/'), $metadata);
    }

    public function testThatSupportsReturnsTrueWithProperUser(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $SecurityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($SecurityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new UserValueResolver($tokenStorage, $userResource);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        static::assertTrue($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     */
    public function testThatResolveCallsExpectedResourceMethod(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $userResource
            ->expects(static::once())
            ->method('findOne');

        $SecurityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($SecurityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new UserValueResolver($tokenStorage, $userResource);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        // Note that we need to actually get current value here
        $resolver->resolve(Request::create('/'), $metadata)->current();
    }

    /**
     * @throws Throwable
     */
    public function testThatResolveReturnsExpectedUser(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $SecurityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($SecurityUser, 'password', 'provider');

        $userResource
            ->expects(static::once())
            ->method('findOne')
            ->with($SecurityUser->getUsername())
            ->willReturn($user);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new UserValueResolver($tokenStorage, $userResource);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        static::assertSame([$user], iterator_to_array($resolver->resolve(Request::create('/'), $metadata)));
    }

    public function testThatIntegrationWithArgumentResolverReturnsExpectedUser(): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $SecurityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($SecurityUser, 'password', 'provider');

        $userResource
            ->expects(static::once())
            ->method('findOne')
            ->with($SecurityUser->getUsername())
            ->willReturn($user);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $argumentResolver = new ArgumentResolver(null, [new UserValueResolver($tokenStorage, $userResource)]);

        $closure = function (User $user) {
            // Do nothing
        };

        static::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @dataProvider dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet
     *
     * @param Closure $closure
     */
    public function testThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(Closure $closure): void
    {
        /** @var MockObject|UserResource $userResource */
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $tokenStorage = new TokenStorage();
        $argumentResolver = new ArgumentResolver(
            null,
            [new UserValueResolver($tokenStorage, $userResource), new DefaultValueResolver()]
        );

        static::assertSame([null], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(): Generator
    {
        yield [function (User $user = null) {
            // Do nothing
        }];

        yield [function (?User $user = null) {
            // Do nothing
        }];

        yield [function (?User $user) {
            // Do nothing
        }];
    }
}
