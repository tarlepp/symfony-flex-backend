<?php
declare(strict_types = 1);
/**
 * /src/Security/Handler/TranslatedAuthenticationFailureHandler.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Handler;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class TranslatedAuthenticationFailureHandler
 *
 * @package App\Security\Handler
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TranslatedAuthenticationFailureHandler extends AuthenticationFailureHandler
{
    private TranslatorInterface $translator;

    /**
     * TranslatedAuthenticationFailureHandler constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher, TranslatorInterface $translator)
    {
        parent::__construct($dispatcher);

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $event = new AuthenticationFailureEvent(
            $exception,
            new JWTAuthenticationFailureResponse(
                $this->translator->trans('Invalid credentials.', [], 'security')
            )
        );

        $this->dispatcher->dispatch($event);

        return $event->getResponse();
    }
}
