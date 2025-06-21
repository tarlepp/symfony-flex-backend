<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Handler/TranslatedAuthenticationFailureHandlerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Handler;

use App\Security\Handler\TranslatedAuthenticationFailureHandler;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @package App\Tests\Integration\Security\Handler
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TranslatedAuthenticationFailureHandlerTest extends KernelTestCase
{
    #[TestDox('Test that `onAuthenticationFailure` method calls expected service methods')]
    public function testThatOnAuthenticationFailureMethodCallsExpectedServiceMethods(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();

        $translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'Invalid credentials.',
                [],
                'security',
            )
            ->willReturn('Invalid credentials.');

        $dispatcher
            ->expects($this->once())
            ->method('dispatch');

        $request = new Request();
        $exception = new AuthenticationException('Invalid credentials.');

        new TranslatedAuthenticationFailureHandler($dispatcher, $translator)
            ->onAuthenticationFailure($request, $exception);
    }
}
