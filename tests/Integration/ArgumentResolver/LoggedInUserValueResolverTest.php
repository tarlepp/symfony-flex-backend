<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ArgumentResolver/LoggedInUserValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\ArgumentResolver;

use App\ArgumentResolver\LoggedInUserValueResolver;
use App\Entity\User;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use Closure;
use Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;
use function iterator_to_array;

/**
 * Class LoggedInUserValueResolverTest
 *
 * @package App\Tests\Integration\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoggedInUserValueResolverTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` methods returns `false` if the parameter name isn't `loggedInUser`
     */
    public function testThatSupportsReturnFalseWithWrongName(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('foobar', null, false, false, null);

        self::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` methods returns `false` if the parameter name is `loggedInUser` but type is wrong
     */
    public function testThatSupportsReturnFalseWithWrongType(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', null, false, false, null);

        self::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` returns `false` if the parameter name isn't `loggedInUser` but type is correct
     */
    public function testThatSupportsReturnFalseWithWrongNameAndCorrectType(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('foobar', User::class, false, false, null);

        self::assertFalse($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` method returns `true` if parameter is nullable
     */
    public function testThatSupportsReturnFalseWithNullable(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null, true);

        self::assertTrue($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` method throws an exception when `userService->getSecurityUser` returns null
     */
    public function testThatSupportsReturnFalseWithNoToken(): void
    {
        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('JWT Token not found');

        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        $resolver->supports(Request::create('/'), $metadata);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` throws an exception when `userService->getSecurityUser` returns non `SecurityUser`
     */
    public function testThatSupportsThrowsAnExceptionWithNonSecurityUser(): void
    {
        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('JWT Token not found');

        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $userService
            ->expects(self::once())
            ->method('getSecurityUser')
            ->willReturn(null);

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        $resolver->supports(Request::create('/'), $metadata);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supports` methods returns `true` with proper `SecurityUser`
     */
    public function testThatSupportsReturnsTrueWithProperUser(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $securityUser = new SecurityUser(new User());

        $userService
            ->expects(self::once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        self::assertTrue($resolver->supports(Request::create('/'), $metadata));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `resolve` method calls expected `UserService` service method
     */
    public function testThatResolveCallsExpectedResourceMethod(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $userService
            ->expects(self::once())
            ->method('getUser');

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);
        $request = Request::create('/');

        $resolver->supports($request, $metadata);

        // Note that we need to actually get current value here
        $resolver->resolve($request, $metadata)->current();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `resolve` method returns expected user
     */
    public function testThatResolveReturnsExpectedUser(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $user = new User();

        $userService
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);
        $request = Request::create('/');

        $resolver->supports($request, $metadata);

        self::assertSame([$user], iterator_to_array($resolver->resolve($request, $metadata)));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that integration with `ArgumentResolver` returns expected user
     */
    public function testThatIntegrationWithArgumentResolverReturnsExpectedUser(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $securityUser = new SecurityUser($user);

        $userService
            ->expects(self::once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $userService
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $argumentResolver = new ArgumentResolver(null, [new LoggedInUserValueResolver($userService)]);

        $closure = static function (User $loggedInUser): void {
            // Do nothing
        };

        self::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @dataProvider dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet
     *
     * @throws Throwable
     *
     * @testdox Test that integration with `ArgumentResolver` returns null when there is not user present
     */
    public function testThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(Closure $closure): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $argumentResolver = new ArgumentResolver(
            null,
            [new LoggedInUserValueResolver($userService), new DefaultValueResolver()]
        );

        self::assertSame([null], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @return Generator<array{0: Closure}>
     */
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
