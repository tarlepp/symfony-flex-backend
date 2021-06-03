<?php
declare(strict_types = 1);
/**
 * /src/Security/Authenticator/ApiKeyAuthenticator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Authenticator;

use App\Security\Provider\ApiKeyUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use function preg_match;

/**
 * Class ApiKeyAuthenticator
 *
 * @package App\Security\Authenticator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiKeyUserProvider $apiKeyUserProvider,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $matches = $this->getMatches($request);

        return !empty($matches);
    }

    public function authenticate(Request $request): PassportInterface
    {
        $matches = $this->getMatches($request);
        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($matches[1]);

        if ($apiKey === null) {
            throw new UserNotFoundException();
        }

        return new SelfValidatingPassport(new UserBadge($matches[1]));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'code' => 401,
            'message' => 'Invalid ApiKey',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param Request $request
     *
     * @return array<mixed>
     */
    private function getMatches(Request $request)
    {
        $apiKey = $request->headers->get('Authorization', '');

        preg_match('#^ApiKey (\w+)$#', $apiKey, $matches);

        return $matches;
    }
}
