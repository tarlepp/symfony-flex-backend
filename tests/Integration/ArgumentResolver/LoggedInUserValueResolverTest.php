<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ArgumentResolver/LoggedInUserValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\ArgumentResolver;

use App\ArgumentResolver\LoggedInUserValueResolver;
use App\Entity\User;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use Closure;
use Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
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
 * Class LoggedInUserValueResolverTest
 *
 * @package App\Tests\Integration\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedInUserValueResolverTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatSupportsReturnFalseWithWrongType(): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $tokenStorage = new TokenStorage();
        $resolver = new LoggedInUserValueResolver($tokenStorage, $userService);
        $metadata = new ArgumentMetadata('foo', null, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     */
    public function testThatSupportsReturnFalseWithNoToken(): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $tokenStorage = new TokenStorage();
        $resolver = new LoggedInUserValueResolver($tokenStorage, $userService);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     */
    public function testThatSupportsThrowsAnExceptionWithNonSecurityUser(): void
    {
        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('JWT Token not found');

        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $token = new UsernamePasswordToken('username', 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        $resolver->supports(Request::create('/'), $metadata);
    }

    /**
     * @throws Throwable
     */
    public function testThatSupportsReturnsTrueWithProperUser(): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $securityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $userService
            ->expects(static::once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        static::assertTrue($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     */
    public function testThatResolveThrowsAnExceptionIfTokenIsNotPresent(): void
    {
        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('JWT Token not found');

        /**
         * @var MockObject|UserTypeIdentification $userService
         */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        (new LoggedInUserValueResolver(new TokenStorage(), $userService))
            ->resolve(new Request(), new ArgumentMetadata('loggedInUser', null, false, false, null))
            ->current();
    }

    /**
     * @throws Throwable
     */
    public function testThatResolveCallsExpectedResourceMethod(): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $userService
            ->expects(static::once())
            ->method('getUser');

        $securityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $userService);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        // Note that we need to actually get current value here
        $resolver->resolve(Request::create('/'), $metadata)->current();
    }

    /**
     * @throws Throwable
     */
    public function testThatResolveReturnsExpectedUser(): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $userService
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $resolver = new LoggedInUserValueResolver($tokenStorage, $userService);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        static::assertSame([$user], iterator_to_array($resolver->resolve(Request::create('/'), $metadata)));
    }

    /**
     * @throws Throwable
     */
    public function testThatIntegrationWithArgumentResolverReturnsExpectedUser(): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'password', 'provider');

        $userService
            ->expects(static::once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $userService
            ->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $argumentResolver = new ArgumentResolver(null, [new LoggedInUserValueResolver($tokenStorage, $userService)]);

        $closure = static function (User $loggedInUser): void {
            // Do nothing
        };

        static::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @dataProvider dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet
     *
     * @throws Throwable
     */
    public function testThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(Closure $closure): void
    {
        /** @var MockObject|UserTypeIdentification $userService */
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $tokenStorage = new TokenStorage();
        $argumentResolver = new ArgumentResolver(
            null,
            [new LoggedInUserValueResolver($tokenStorage, $userService), new DefaultValueResolver()]
        );

        static::assertSame([null], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    public function dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(): Generator
    {
        yield [static function (?User $loggedInUser = null): void {
            // Do nothing
        }];

        yield [static function (?User $loggedInUser = null): void {
            // Do nothing
        }];

        yield [static function (?User $loggedInUser): void {
            // Do nothing
        }];
    }
}
