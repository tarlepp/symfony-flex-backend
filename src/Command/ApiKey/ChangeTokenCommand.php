<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ChangeTokenCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class ChangeTokenCommand
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ChangeTokenCommand extends Command
{
    use SymfonyStyleTrait;

    private ApiKeyResource $apiKeyResource;
    private ApiKeyHelper $apiKeyHelper;

    /**
     * ChangeTokenCommand constructor.
     */
    public function __construct(ApiKeyResource $apiKeyResource, ApiKeyHelper $apiKeyHelper)
    {
        parent::__construct('api-key:change-token');

        $this->apiKeyResource = $apiKeyResource;
        $this->apiKeyHelper = $apiKeyHelper;

        $this->setDescription('Command to change token for existing API key');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Get API key entity
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key token you want to change?');
        $message = null;

        if ($apiKey instanceof ApiKeyEntity) {
            $message = $this->changeApiKeyToken($apiKey);
        }

        if ($input->isInteractive()) {
            $message ??= 'Nothing changed - have a nice day';

            $io->success($message);
        }

        return 0;
    }

    /**
     * Method to change API key token.
     *
     * @return mixed[]
     *
     * @throws Throwable
     */
    private function changeApiKeyToken(ApiKeyEntity $apiKey): array
    {
        // Generate new token for API key
        $apiKey->generateToken();

        // Update API key
        $this->apiKeyResource->save($apiKey);

        return $this->apiKeyHelper->getApiKeyMessage('API key token updated - have a nice day', $apiKey);
    }
}
