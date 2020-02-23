<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ExceptionSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Utils\JSON;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;
use function get_class;

/**
 * Class ExceptionSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private string $environment;

    /**
     * ExceptionSubscriber constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface       $logger
     * @param string                $environment
     */
    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger, string $environment)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array<string, array<int, string|int>> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => [
                'onKernelException',
                -100,
            ],
        ];
    }

    /**
     * Method to handle kernel exception.
     *
     * @param ExceptionEvent $event
     *
     * @throws JsonException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // Get exception from current event
        $exception = $event->getThrowable();

        // Log  error
        $this->logger->error((string)$exception);

        // Create new response
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setStatusCode($this->getStatusCode($exception));
        $response->setContent(JSON::encode($this->getErrorMessage($exception, $response)));

        // Send the modified response object to the event
        $event->setResponse($response);
    }

    /**
     * Method to get "proper" status code for exception response.
     *
     * @param Throwable $exception
     *
     * @return int
     */
    private function getStatusCode(Throwable $exception): int
    {
        // Get current token, and determine if request is made from logged in user or not
        $token = $this->tokenStorage->getToken();
        $isUser = !($token === null || $token instanceof AnonymousToken);

        return $this->determineStatusCode($exception, $isUser);
    }

    /**
     * Method to get actual error message.
     *
     * @param Throwable $exception
     * @param Response  $response
     *
     * @return mixed[]
     */
    private function getErrorMessage(Throwable $exception, Response $response): array
    {
        // Set base of error message
        $error = [
            'message' => $this->getExceptionMessage($exception),
            'code' => $exception->getCode(),
            'status' => $response->getStatusCode(),
        ];

        // Attach more info to error response in dev environment
        if ($this->environment === 'dev') {
            $error += [
                'debug' => [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTrace(),
                    'traceString' => $exception->getTraceAsString(),
                ],
            ];
        }

        return $error;
    }

    /**
     * Helper method to convert exception message for user. This method is used in 'production' environment so, that
     * application won't reveal any sensitive error data to users.
     *
     * @param Throwable $exception
     *
     * @return string
     */
    private function getExceptionMessage(Throwable $exception): string
    {
        return $this->environment === 'dev'
            ? $exception->getMessage()
            : $this->getMessageForProductionEnvironment($exception);
    }

    /**
     * @param Throwable $exception
     *
     * @return string
     */
    private function getMessageForProductionEnvironment(Throwable $exception): string
    {
        $message = $exception->getMessage();

        // Within AccessDeniedHttpException we need to hide actual real message from users
        if ($exception instanceof AccessDeniedHttpException || $exception instanceof AccessDeniedException) {
            $message = 'Access denied.';
        } elseif ($exception instanceof DBALException || $exception instanceof ORMException) { // Database errors
            $message = 'Database error.';
        }

        return $message;
    }

    /**
     * Method to determine status code for specified exception.
     *
     * @param Throwable $exception
     * @param bool      $isUser
     *
     * @return int
     */
    private function determineStatusCode(Throwable $exception, bool $isUser): int
    {
        // Default status code is always 500
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        // HttpExceptionInterface is a special type of exception that holds status code and header details
        if ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof AccessDeniedException) {
            $statusCode = $isUser ? Response::HTTP_FORBIDDEN : Response::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        return $statusCode;
    }
}
