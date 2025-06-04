<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ApiKeyHelper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Entity\ApiKey;
use App\Resource\ApiKeyResource;
use App\Security\RolesService;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function implode;
use function sprintf;

/**
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyHelper
{
    public function __construct(
        private readonly ApiKeyResource $apiKeyResource,
        private readonly RolesService $rolesService,
    ) {
    }

    /**
     * Method to get API key entity. Also note that this may return a null in
     * cases that user do not want to make any changes to API keys.
     *
     * @throws Throwable
     */
    public function getApiKey(SymfonyStyle $io, string $question): ?ApiKey
    {
        $found = false;
        $apiKey = null;

        while ($found !== true) {
            $apiKey = $this->getApiKeyEntity($io, $question);

            if (!$apiKey instanceof ApiKey) {
                break;
            }

            $message = sprintf(
                'Is this the correct API key \'[%s] [%s] %s\'?',
                $apiKey->getId(),
                $apiKey->getToken(),
                $apiKey->getDescription(),
            );

            $found = $io->confirm($message, false);
        }

        return $apiKey ?? null;
    }

    /**
     * Helper method to get "normalized" message for API key. This is used on
     * following cases:
     *  - User changes API key token
     *  - User creates new API key
     *  - User modifies API key
     *  - User removes API key
     *
     * @return array<int, string>
     */
    public function getApiKeyMessage(string $message, ApiKey $apiKey): array
    {
        return [
            $message,
            sprintf(
                "GUID:  %s\nToken: %s",
                $apiKey->getId(),
                $apiKey->getToken(),
            ),
        ];
    }

    /**
     * Method to list ApiKeys where user can select desired one.
     *
     * @throws Throwable
     */
    private function getApiKeyEntity(SymfonyStyle $io, string $question): ?ApiKey
    {
        /** @var array<string, string> $choices */
        $choices = [];

        foreach ($this->apiKeyResource->find() as $apiKey) {
            $choices[$apiKey->getId()] = sprintf(
                '[Token: %s] %s - Roles: %s',
                $apiKey->getToken(),
                $apiKey->getDescription(),
                implode(', ', $this->rolesService->getInheritedRoles($apiKey->getRoles())),
            );
        }

        $choices['Exit'] = 'Exit command';

        return $this->apiKeyResource->findOne((string)$io->choice($question, $choices));
    }
}
