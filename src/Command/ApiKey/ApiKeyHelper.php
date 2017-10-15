<?php
declare(strict_types=1);
/**
 * /src/Command/ApiKey/ApiKeyHelper.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\Entity\ApiKey;
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
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return ApiKey
     */
    public function getApiKey(SymfonyStyle $io, string $question): ?ApiKey
    {
        $choices = [];

        /**
         * Lambda function create api key choices
         *
         * @param ApiKey $apiKey
         */
        $iterator = function (ApiKey $apiKey) use (&$choices): void {
            $message = \sprintf(
                '[%s] %s',
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $choices[$apiKey->getId()] = $message;
        };

        \array_map($iterator, $this->apiKeyResource->find([], ['token' => 'ASC']));

        $choices['Exit'] = 'Exit command';

        return $this->apiKeyResource->findOne($io->choice($question, $choices));
    }
}
