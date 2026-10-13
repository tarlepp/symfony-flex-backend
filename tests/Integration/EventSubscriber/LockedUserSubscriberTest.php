<?php
declare(strict_types = 1);

/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
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

final class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `onAuthenticationSuccess` method throws `UnsupportedUserException` when user is not supported',
    )]
    public function testThatOnAuthenticationSuccessThrowsUserNotFoundException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageIsOrContains('Unsupported user.');

        $userRepositoryStub = self::createStub(UserRepository::class);
        $logLoginFailureResourceStub = self::createStub(LogLoginFailureResource::class);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $event = new AuthenticationSuccessEvent(
            [],
            new InMemoryUser('username', 'password'),
            new Response(),
        );

        new LockedUserSubscriber($userRepositoryStub, $logLoginFailureResourceStub, $requestStack)
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `onAuthenticationSuccess` throws `LockedException` when user is locked')]
    public function testThatOnAuthenticationSuccessThrowsLockedException(): void
    {
        $this->expectException(LockedException::class);
        $this->expectExceptionMessageIsOrContains('Locked account.');

        $userRepositoryMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResourceStub = self::createStub(LogLoginFailureResource::class);
        $userMock = $this->getMockBuilder(UserEntity::class)->getMock();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $uuid = UuidHelper::getFactory()->uuid1();

        $userMock
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn(Language::EN);

        $userMock
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn(Locale::EN);

        $userMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($uuid->toString());

        $userMock
            ->expects($this->once())
            ->method('getLogsLoginFailure')
            ->willReturn(new ArrayCollection(range(0, 11)));

        $userRepositoryMock
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($userMock->getId())
            ->willReturn($userMock);

        $securityUser = new SecurityUser($userMock);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceStub, $requestStack)
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `onAuthenticationSuccess` method calls resource service `reset` method when user is not locked',
    )]
    public function testThatOnAuthenticationSuccessResourceResetMethodIsCalled(): void
    {
        $userRepositoryMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResourceMock = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $user = new UserEntity();

        $userRepositoryMock
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($user->getId())
            ->willReturn($user);

        $logLoginFailureResourceMock
            ->expects($this->once())
            ->method('reset')
            ->with($user);

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock, $requestStack)
            ->onAuthenticationSuccess($event);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogLoginFailureResource::save` method is not called when user is not found')]
    public function testThatOnAuthenticationFailureTestThatResourceMethodsAreNotCalledWhenWrongUser(): void
    {
        $userRepositoryMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResourceMock = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepositoryMock
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test-user')
            ->willReturn(null);

        $logLoginFailureResourceMock
            ->expects(self::never())
            ->method(self::anything());

        $requestStack = new RequestStack();
        $requestStack->push(new Request([
            'username' => 'test-user',
        ]));

        new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock, $requestStack)
            ->onAuthenticationFailure();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogLoginFailureResource::save` method is called when user is found')]
    public function testThatOnAuthenticationFailureTestThatResourceSaveMethodIsCalled(): void
    {
        $user = new User();

        $userRepositoryMock = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logLoginFailureResourceMock = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepositoryMock
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('john')
            ->willReturn($user);

        $logLoginFailureResourceMock
            ->expects($this->once())
            ->method('save');

        $request = new Request([
            'username' => 'john',
            'password' => 'wrong-password',
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $logLoginFailureResourceMock
            ->expects(static::once())
            ->method('save');

        new LockedUserSubscriber($userRepositoryMock, $logLoginFailureResourceMock, $requestStack)
            ->onAuthenticationFailure();
    }
}
