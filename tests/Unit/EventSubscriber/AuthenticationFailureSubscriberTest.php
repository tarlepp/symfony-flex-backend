<?php
declare(strict_types = 1);

/**
 * /tests/Unit/EventSubscriber/AuthenticationFailureSubscriberTest.php
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthenticationFailureSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuthenticationFailureSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure',
        ];

        self::assertSame($expected, AuthenticationFailureSubscriber::getSubscribedEvents());
    }
}
