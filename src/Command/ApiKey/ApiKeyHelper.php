<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ApiKeyHelper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\ApiKey;

use App\Entity\ApiKey as ApiKeyEntity;
use App\Resource\ApiKeyResource;
use App\Security\RolesService;
use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_map;
use function implode;
use function sprintf;

/**
 * Class ApiKeyHelper
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyHelper
{
    private ApiKeyResource $apiKeyResource;
    private RolesService $rolesService;

    /**
     * ApiKeyHelper constructor.
     */
    public function __construct(ApiKeyResource $apiKeyResource, RolesService $rolesService)
    {
        $this->apiKeyResource = $apiKeyResource;
        $this->rolesService = $rolesService;
    }

    /**
     * Method to get API key entity. Also note that this may return a null in
     * cases that user do not want to make any changes to API keys.
     *
     * @throws Throwable
     */
    public function getApiKey(SymfonyStyle $io, string $question): ?ApiKeyEntity
    {
        $apiKeyFound = false;
        $apiKeyEntity = null;

        while ($apiKeyFound !== true) {
            /** @var ApiKeyEntity|null $apiKeyEntity */
            $apiKeyEntity = $this->getApiKeyEntity($io, $question);

            if ($apiKeyEntity === null) {
                break;
            }

            $message = sprintf(
                'Is this the correct API key \'[%s] [%s] %s\'?',
                $apiKeyEntity->getId(),
                $apiKeyEntity->getToken(),
                $apiKeyEntity->getDescription()
            );

            $apiKeyFound = (bool)$io->confirm($message, false);
        }

        return $apiKeyEntity ?? null;
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
    public function getApiKeyMessage(string $message, ApiKeyEntity $apiKey): array
    {
        return [
            $message,
            sprintf(
                "GUID:  %s\nToken: %s",
                $apiKey->getId(),
                $apiKey->getToken()
            ),
        ];
    }

    /**
     * Method to list ApiKeys where user can select desired one.
     *
     * @throws Throwable
     */
    private function getApiKeyEntity(SymfonyStyle $io, string $question): ?ApiKeyEntity
    {
        $choices = [];
        $iterator = $this->getApiKeyIterator($choices);

        array_map($iterator, $this->apiKeyResource->find(null, ['token' => 'ASC']));

        $choices['Exit'] = 'Exit command';

        return $this->apiKeyResource->findOne((string)$io->choice($question, $choices));
    }

    /**
     * Method to return ApiKeyIterator closure. This will format ApiKey
     * entities for choice list.
     *
     * @param string[] $choices
     */
    private function getApiKeyIterator(array &$choices): Closure
    {
        /*
         * Lambda function create api key choices
         *
         * @param ApiKeyEntity $apiKey
         */
        return function (ApiKeyEntity $apiKey) use (&$choices): void {
            $value = sprintf(
                '[%s] %s - Roles: %s',
                $apiKey->getToken(),
                $apiKey->getDescription(),
                implode(', ', $this->rolesService->getInheritedRoles($apiKey->getRoles()))
            );

            $choices[$apiKey->getId()] = $value;
        };
    }
}
