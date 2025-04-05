<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ValueResolver/LoggedInUserValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\ValueResolver;

use App\Entity\User;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\ValueResolver\LoggedInUserValueResolver;
use Closure;
use Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;
use function iterator_to_array;

/**
 * @package App\Tests\Integration\ValueResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LoggedInUserValueResolverTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that `supports` methods returns `false` if the parameter name isn't `loggedInUser`")]
    public function testThatSupportsReturnFalseWithWrongName(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('foobar', null, false, false, null);

        self::assertFalse($resolver->supports($metadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `supports` methods returns `false` if the parameter name is `loggedInUser` but type is wrong')]
    public function testThatSupportsReturnFalseWithWrongType(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', null, false, false, null);

        self::assertFalse($resolver->supports($metadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that `supports` returns `false` if the parameter name isn't `loggedInUser` but type is correct")]
    public function testThatSupportsReturnFalseWithWrongNameAndCorrectType(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('foobar', User::class, false, false, null);

        self::assertFalse($resolver->supports($metadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `supports` method returns `true` if parameter is nullable')]
    public function testThatSupportsReturnFalseWithNullable(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null, true);

        self::assertTrue($resolver->supports($metadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `supports` method throws an exception when `userService->getSecurityUser` returns null')]
    public function testThatSupportsReturnFalseWithNoToken(): void
    {
        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('JWT Token not found');

        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        $resolver->supports($metadata);
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `supports` throws an exception when `userService->getSecurityUser` returns non `SecurityUser`'
    )]
    public function testThatSupportsThrowsAnExceptionWithNonSecurityUser(): void
    {
        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('JWT Token not found');

        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $userService
            ->expects($this->once())
            ->method('getSecurityUser')
            ->willReturn(null);

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        $resolver->supports($metadata);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `supports` methods returns `true` with proper `SecurityUser`')]
    public function testThatSupportsReturnsTrueWithProperUser(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $securityUser = new SecurityUser(new User());

        $userService
            ->expects($this->once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);

        self::assertTrue($resolver->supports($metadata));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `resolve` method calls expected `UserService` service method')]
    public function testThatResolveCallsExpectedResourceMethod(): void
    {
        $securityUser = new SecurityUser(new User());

        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $userService
            ->expects($this->once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $userService
            ->expects($this->once())
            ->method('getUser');

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);
        $request = Request::create('/');

        // Note that we need to actually get current value here
        $resolver->resolve($request, $metadata)->current();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `resolve` method returns expected user')]
    public function testThatResolveReturnsExpectedUser(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $securityUser = new SecurityUser($user);

        $userService
            ->expects($this->once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $userService
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $resolver = new LoggedInUserValueResolver($userService);
        $metadata = new ArgumentMetadata('loggedInUser', User::class, false, false, null);
        $request = Request::create('/');

        self::assertSame([$user], iterator_to_array($resolver->resolve($request, $metadata)));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that integration with `ArgumentResolver` returns expected user')]
    public function testThatIntegrationWithArgumentResolverReturnsExpectedUser(): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $securityUser = new SecurityUser($user);

        $userService
            ->expects($this->once())
            ->method('getSecurityUser')
            ->willReturn($securityUser);

        $userService
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $argumentResolver = new ArgumentResolver(null, [new LoggedInUserValueResolver($userService)]);

        $closure = static function (User $loggedInUser): void {
            // Do nothing
        };

        self::assertSame([$user], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet')]
    #[TestDox('Test that integration with `ArgumentResolver` returns null when there is no user present')]
    public function testThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(Closure $closure): void
    {
        $userService = $this->getMockBuilder(UserTypeIdentification::class)->disableOriginalConstructor()->getMock();

        $argumentResolver = new ArgumentResolver(
            null,
            [new LoggedInUserValueResolver($userService), new DefaultValueResolver()]
        );

        self::assertSame([null], $argumentResolver->getArguments(Request::create('/'), $closure));
    }

    public static function dataProviderTestThatIntegrationWithArgumentResolverReturnsNullWhenUserNotSet(): Generator
    {
        yield 'closure with nullable user' => [static function (?User $loggedInUser = null): void {
            // Do nothing
        }];

        yield 'closure without nullable user' => [static function (?User $loggedInUser): void {
            // Do nothing
        }];
    }
}
