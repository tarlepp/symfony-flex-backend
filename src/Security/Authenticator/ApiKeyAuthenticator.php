<?php
declare(strict_types = 1);
/**
 * /src/Security/Authenticator/ApiKeyAuthenticator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Authenticator;

use App\Entity\ApiKey;
use App\Security\Interfaces\ApiKeyUserInterface;
use App\Security\Provider\ApiKeyUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use function is_array;
use function preg_match;

/**
 * Class ApiKeyAuthenticator
 *
 * @package App\Security\Authenticator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    private const CREDENTIAL_KEY = 'token';

    public function __construct(
        private ApiKeyUserProvider $apiKeyUserProvider,
    ) {
    }

    public function supports(Request $request): bool
    {
        $apiKey = $request->headers->get('Authorization', '');

        preg_match('#^ApiKey (\w+)$#', $apiKey, $matches);

        return !empty($matches);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array<string, string>|null
     */
    public function getCredentials(Request $request): ?array
    {
        $output = null;

        $apiKey = $request->headers->get('Authorization', '');

        preg_match('#^ApiKey (\w+)$#', $apiKey, $matches);

        if (!empty($matches)) {
            $output = [
                self::CREDENTIAL_KEY => $matches[1],
            ];
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?ApiKeyUserInterface
    {
        $apiToken = $this->getApiKeyToken($credentials);

        return $apiToken === null ? null : $this->apiKeyUserProvider->loadUserByUsername($apiToken);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $apiToken = $this->getApiKeyToken($credentials) ?? throw new AuthenticationException('Invalid token');

        return $this->apiKeyUserProvider->getApiKeyForToken($apiToken) instanceof ApiKey;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function getApiKeyToken(mixed $credentials): ?string
    {
        return is_array($credentials) ? $credentials[self::CREDENTIAL_KEY] ?? null : null;
    }
}
