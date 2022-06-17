<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ExceptionSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Exception\interfaces\ClientErrorInterface;
use App\Security\UserTypeIdentification;
use App\Utils\JSON;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\ORMException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;
use function array_intersect;
use function array_key_exists;
use function class_implements;
use function in_array;
use function spl_object_hash;

/**
 * Class ExceptionSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string, bool>
     */
    private static array $cache = [];

    /**
     * @var array<int, string>
     */
    private static array $clientExceptions = [
        HttpExceptionInterface::class,
        ClientErrorInterface::class,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly UserTypeIdentification $userService,
        private readonly string $environment,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, array<int, string|int>>
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
     */
    private function getStatusCode(Throwable $exception): int
    {
        return $this->determineStatusCode($exception, $this->userService->getSecurityUser() !== null);
    }

    /**
     * Method to get actual error message.
     *
     * @return array<string, mixed>
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
                    'exception' => $exception::class,
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
     * Helper method to convert exception message for user. This method is
     * used in 'production' environment so, that application won't reveal any
     * sensitive error data to users.
     */
    private function getExceptionMessage(Throwable $exception): string
    {
        return $this->environment === 'dev'
            ? $exception->getMessage()
            : $this->getMessageForProductionEnvironment($exception);
    }

    private function getMessageForProductionEnvironment(Throwable $exception): string
    {
        $message = $exception->getMessage();

        $accessDeniedClasses = [
            AccessDeniedHttpException::class,
            AccessDeniedException::class,
            AuthenticationException::class,
        ];

        if (in_array($exception::class, $accessDeniedClasses, true)) {
            $message = 'Access denied.';
        } elseif ($exception instanceof Exception || $exception instanceof ORMException) {
            // Database errors
            $message = 'Database error.';
        } elseif (!$this->isClientExceptions($exception)) {
            $message = 'Internal server error.';
        }

        return $message;
    }

    /**
     * Method to determine status code for specified exception.
     */
    private function determineStatusCode(Throwable $exception, bool $isUser): int
    {
        $accessDeniedException = static fn (bool $isUser): int => $isUser
            ? Response::HTTP_FORBIDDEN
            : Response::HTTP_UNAUTHORIZED;

        $clientException = static fn (HttpExceptionInterface|ClientErrorInterface|Throwable $exception): int =>
            $exception instanceof HttpExceptionInterface || $exception instanceof  ClientErrorInterface
                ? $exception->getStatusCode()
                : (int)$exception->getCode();

        $output = match (true) {
            $exception instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
            $exception instanceof AccessDeniedException => $accessDeniedException($isUser),
            $this->isClientExceptions($exception) => $clientException($exception),
            default => 0,
        };

        return $output > 0 ? $output : Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Method to check if exception is ok to show to user (client) or not. Note
     * that if this returns true exception message is shown as-is to user.
     */
    private function isClientExceptions(Throwable $exception): bool
    {
        $cacheKey = spl_object_hash($exception);

        if (!array_key_exists($cacheKey, self::$cache)) {
            $intersect = array_intersect((array)class_implements($exception), self::$clientExceptions);

            self::$cache[$cacheKey] = $intersect !== [];
        }

        return self::$cache[$cacheKey];
    }
}
