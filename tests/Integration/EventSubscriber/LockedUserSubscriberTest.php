<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LockedUserSubscriber;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureCallsExpectedServiceMethod(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $token = new UsernamePasswordToken('test-user', 'password', 'providerKey');
        $token->setUser(new SecurityUser($user));

        $authenticationException = new AuthenticationException();
        $authenticationException->setToken($token);

        /**
         * @var MockObject|UserRepository          $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($user->getId())
            ->willReturn($user);

        $logLoginFailureResource
            ->expects(static::once())
            ->method('save');

        $subscriber = new LockedUserSubscriber($userRepository, $logLoginFailureResource);
        $subscriber->onAuthenticationFailure(new AuthenticationFailureEvent($authenticationException, new Response()));
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessCallsExpectedServiceMethod(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $securityUser = new SecurityUser($user);
        $event = new AuthenticationSuccessEvent([], $securityUser, new Response());

        /**
         * @var MockObject|UserRepository          $userRepository
         * @var MockObject|LogLoginFailureResource $logLoginFailureResource
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $logLoginFailureResource = $this->getMockBuilder(LogLoginFailureResource::class)
            ->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($user->getId())
            ->willReturn($user);

        $logLoginFailureResource
            ->expects(static::once())
            ->method('reset')
            ->with($user);

        $subscriber = new LockedUserSubscriber($userRepository, $logLoginFailureResource);
        $subscriber->onAuthenticationSuccess($event);
    }
}
