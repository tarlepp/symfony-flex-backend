<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ApiKeyHelper.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\Entity\ApiKey as ApiKeyEntity;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ApiKeyHelper
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyHelper
{
    /**
     * @var ApiKeyResource
     */
    private $apiKeyResource;

    /**
     * ApiKeyHelper constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     */
    public function __construct(ApiKeyResource $apiKeyResource)
    {
        $this->apiKeyResource = $apiKeyResource;
    }

    /**
     * Method to get API key entity. Also note that this may return a null in cases that user do not want to make any
     * changes to API keys.
     *
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return ApiKeyEntity|null
     */
    public function getApiKey(SymfonyStyle $io, string $question): ?ApiKeyEntity
    {
        $apiKeyFound = false;

        while ($apiKeyFound === false) {
            $apiKeyEntity = $this->getApiKeyEntity($io, $question);

            if ($apiKeyEntity === null) {
                break;
            }

            $message = \sprintf(
                'Is this the correct API key \'[%s] [%s] %s\'?',
                $apiKeyEntity->getId(),
                $apiKeyEntity->getToken(),
                $apiKeyEntity->getDescription()
            );

            $apiKeyFound = $io->confirm($message, false);
        }

        return $apiKeyEntity ?? null;
    }

    /**
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return ApiKeyEntity|null
     */
    private function getApiKeyEntity(SymfonyStyle $io, string $question): ?ApiKeyEntity
    {
        $choices = [];
        $iterator = $this->getApiKeyIterator($choices);

        \array_map($iterator, $this->apiKeyResource->find([], ['token' => 'ASC']));

        $choices['Exit'] = 'Exit command';

        return $this->apiKeyResource->findOne($io->choice($question, $choices));
    }

    /**
     * @param array $choices
     *
     * @return \Closure
     */
    private function getApiKeyIterator(&$choices): \Closure
    {
        /**
         * Lambda function create api key choices
         *
         * @param ApiKeyEntity $apiKey
         */
        $iterator = function (ApiKeyEntity $apiKey) use (&$choices): void {
            $message = \sprintf(
                '[%s] %s',
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $choices[$apiKey->getId()] = $message;
        };

        return $iterator;
    }
}
