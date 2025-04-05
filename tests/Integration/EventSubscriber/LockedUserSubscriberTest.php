<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\Entity\User as UserEntity;
use App\Enum\Language;
use App\Enum\Locale;
use App\EventSubscriber\LockedUserSubscriber;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Rest\UuidHelper;
use App\Security\SecurityUser;
use Doctrine\Common\Collections\ArrayCollection;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;
use function range;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `onAuthenticationSuccess` method throws `UnsupportedUserException` when user is not supported'
    )]
    public function testThatOnAuthenticationSuccessThrowsUserNotFoundException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Unsupported user.');

        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $event = new AuthenticationSuccessEvent(
            [],
            new InMemoryUser('username', 'password'),
            new Response()
        );

        new LockedUserSubscriber($userRepository, $logLoginFailureResource, $requestStack)
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `onAuthenticationSuccess` throws `LockedException` when user is locked')]
    public function testThatOnAuthenticationSuccessThrowsLockedException(): void
    {
        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('Locked account.');

        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(UserEntity::class)->getMock();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $uuid = UuidHelper::getFactory()->uuid1();

        $user
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn(Language::EN);

        $user
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn(Locale::EN);

        $user
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($uuid->toString());

        $user
            ->expects($this->once())
            ->method('getLogsLoginFailure')
            ->willReturn(new ArrayCollection(range(0, 11)));

        $userRepository
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($user->getId())
            ->willReturn($user);

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        new LockedUserSubscriber($userRepository, $logLoginFailureResource, $requestStack)
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `onAuthenticationSuccess` method calls resource service `reset` method when user is not locked'
    )]
    public function testThatOnAuthenticationSuccessResourceResetMethodIsCalled(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $user = new UserEntity();

        $userRepository
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($user->getId())
            ->willReturn($user);

        $logLoginFailureResource
            ->expects($this->once())
            ->method('reset')
            ->with($user);

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        new LockedUserSubscriber($userRepository, $logLoginFailureResource, $requestStack)
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogLoginFailureResource::save` method is not called when user is not found')]
    public function testThatOnAuthenticationFailureTestThatResourceMethodsAreNotCalledWhenWrongUser(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepository
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test-user')
            ->willReturn(null);

        $logLoginFailureResource
            ->expects(self::never())
            ->method(self::anything());

        $requestStack = new RequestStack();
        $requestStack->push(new Request([
            'username' => 'test-user',
        ]));

        new LockedUserSubscriber($userRepository, $logLoginFailureResource, $requestStack)
            ->onAuthenticationFailure();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogLoginFailureResource::save` method is called when user is found')]
    public function testThatOnAuthenticationFailureTestThatResourceSaveMethodIsCalled(): void
    {
        $user = new User();

        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepository
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('john')
            ->willReturn($user);

        $logLoginFailureResource
            ->expects($this->once())
            ->method('save');

        $request = new Request([
            'username' => 'john',
            'password' => 'wrong-password',
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $logLoginFailureResource
            ->expects(static::once())
            ->method('save');

        new LockedUserSubscriber($userRepository, $logLoginFailureResource, $requestStack)
            ->onAuthenticationFailure();
    }
}
