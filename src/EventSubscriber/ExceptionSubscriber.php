<?php
declare(strict_types=1);
/**
 * /src/EventSubscriber/ExceptionSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Utils\JSON;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class ExceptionSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ExceptionSubscriber
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $environment;

    /**
     * ExceptionSubscriber constructor.
     *
     * @param LoggerInterface       $logger
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(LoggerInterface $logger, TokenStorageInterface $tokenStorage)
    {
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->environment = \getenv('APP_ENV');
    }

    /**
     * Method to handle kernel exception.
     *
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        // Get exception from current event
        $exception = $event->getException();

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
     * @param \Exception $exception
     *
     * @return int
     */
    private function getStatusCode(\Exception $exception): int
    {
        // Get current token, and determine if request is made from logged in user or not
        $token = $this->tokenStorage->getToken();
        $user = !($token === null || $token instanceof AnonymousToken);

        // Default status code is always 500
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        // HttpExceptionInterface is a special type of exception that holds status code and header details
        if ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
        } else if ($exception instanceof AccessDeniedException) {
            $statusCode = $user ? Response::HTTP_FORBIDDEN : Response::HTTP_UNAUTHORIZED;
        } else if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        return $statusCode;
    }

    /**
     * Method to get actual error message.
     *
     * @param \Exception    $exception
     * @param Response      $response
     *
     * @return array
     */
    private function getErrorMessage(\Exception $exception, Response $response): array
    {
        // Set base of error message
        $error = [
            'message'   => $this->getExceptionMessage($exception),
            'code'      => $exception->getCode(),
            'status'    => $response->getStatusCode(),
        ];

        // Attach more info to error response in dev environment
        if ($this->environment === 'dev') {
            $error += [
                'debug' => [
                    'file'          => $exception->getFile(),
                    'line'          => $exception->getLine(),
                    'message'       => $exception->getMessage(),
                    'trace'         => $exception->getTrace(),
                    'traceString'   => $exception->getTraceAsString(),
                ],
            ];
        }

        return $error;
    }

    /**
     * Helper method to convert exception message for user. This method is used in 'production' environment so, that
     * application won't reveal any sensitive error data to users.
     *
     * @param \Exception $exception
     *
     * @return string
     */
    private function getExceptionMessage(\Exception $exception): string
    {
        if ($this->environment === 'dev') {
            $message = $exception->getMessage();
        } else {
            // Within AccessDeniedHttpException we need to hide actual real message from users
            if ($exception instanceof AccessDeniedHttpException ||
                $exception instanceof AccessDeniedException
            ) {
                $message = 'Access denied.';
            } else if ($exception instanceof DBALException ||
                $exception instanceof ORMException
            ) { // Database errors
                $message = 'Database error.';
            } else {
                $message = $exception->getMessage();
            }
        }

        return $message;
    }
}
