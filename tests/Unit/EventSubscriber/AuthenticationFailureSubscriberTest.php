<?php
declare(strict_types = 1);
/**
 * /tests/Unit/EventSubscriber/AuthenticationFailureSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AuthenticationFailureSubscriber;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class AuthenticationFailureSubscriberTest
 *
 * @package App\Tests\Unit\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationFailureSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that `getSubscribedEvents` method returns expected')]
    public function testThatGetSubscribedEventsReturnsExpected(): void
    {
        $expected = [
            'Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent' => 'onAuthenticationFailure',
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure',
        ];

        self::assertSame($expected, AuthenticationFailureSubscriber::getSubscribedEvents());
    }
}
