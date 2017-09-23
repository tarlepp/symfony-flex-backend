<?php
declare(strict_types=1);
/**
 * /src/Security/ApiKeyAuthenticator.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

/**
 * Class ApiKeyAuthenticator
 *
 * @see https://symfony.com/doc/current/security/api_key_authentication.html
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    /**
     * Method to create PreAuthenticatedToken if following header is present in current request:
     *  Authorization: ApiKey _some_token_
     *
     * @param Request $request
     * @param string  $providerKey
     *
     * @return null|PreAuthenticatedToken
     */
    public function createToken(Request $request, $providerKey): ?PreAuthenticatedToken
    {
        // Look up 'Authorization' header from current request
        $apiKey = $request->headers->get('Authorization');

        // Authorization header found and it contains 'ApiKey TOKEN' data, so we can make PreAuthenticatedToken
        if ($apiKey && \preg_match('#^ApiKey (\w+)$#', $apiKey, $matches)) {
            $output = new PreAuthenticatedToken('anon', $matches[1], $providerKey);
        }

        return $output ?? null;
    }

    /**
     * Check that current token is supported within this authenticator.
     *
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey): bool
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /**
     * Method to authenticate current token and load user information according to that token. Note that in this point
     * we have multiple user providers so we need to filter those.
     *
     * After that we can try to fetch actual ApiKey entity for current token credentials, which is created within
     * 'createToken' method.
     *
     * Then if ApiKey entity for that credentials is found we can load actual user and create new PreAuthenticatedToken
     * with proper data and return it.
     *
     * @param TokenInterface                          $token
     * @param UserProviderInterface|ChainUserProvider $userProvider
     * @param string                                  $providerKey
     *
     * @return PreAuthenticatedToken
     *
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function authenticateToken(
        TokenInterface $token,
        UserProviderInterface $userProvider,
        $providerKey
    ): PreAuthenticatedToken {
        /**
         * Lambda function to filter user providers
         *
         * @param UserProviderInterface $userProvider
         *
         * @return bool
         */
        $filter =  function (UserProviderInterface $userProvider) {
            return $userProvider instanceof ApiKeyUserProvider;
        };

        // Oh noes, we don't have ApiKeyUserProvider
        if (\count($providers = \array_filter($userProvider->getProviders(), $filter)) !== 1) {
            throw new AuthenticationException('The user provider must be an instance of ApiKeyUserProvider');
        }

        /** @var ApiKeyUserProvider $userProvider */
        $userProvider = \current($providers);

        $identifier = $token->getCredentials();
        $apiKey = $userProvider->getApiKeyForToken($identifier);

        /**
         * Token not found, so cannot continue
         *
         * CAUTION: this message will be returned to the client (so don't put any un-trusted messages / error strings
         *          here)
         */
        if ($apiKey === null) {
            throw new CustomUserMessageAuthenticationException(\sprintf('API Key "%s" does not exist.', $identifier));
        }

        $user = $userProvider->loadUserByUsername($apiKey->getId());

        return new PreAuthenticatedToken(
            $user,
            $identifier,
            $providerKey,
            $user->getRoles()
        );
    }
}
