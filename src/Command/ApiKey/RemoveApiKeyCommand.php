<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/RemoveApiKeyCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class RemoveApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RemoveApiKeyCommand extends Command
{
    use SymfonyStyleTrait;

    private ApiKeyResource $apiKeyResource;
    private ApiKeyHelper $apiKeyHelper;

    /**
     * RemoveApiKeyCommand constructor.
     *
     * @throws LogicException
     */
    public function __construct(ApiKeyResource $apiKeyResource, ApiKeyHelper $apiKeyHelper)
    {
        parent::__construct('api-key:remove');

        $this->apiKeyResource = $apiKeyResource;
        $this->apiKeyHelper = $apiKeyHelper;

        $this->setDescription('Console command to remove existing API key');
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
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key you want to remove?');
        $message = null;

        if ($apiKey instanceof ApiKeyEntity) {
            // Delete API key
            $this->apiKeyResource->delete($apiKey->getId());

            $message = $this->apiKeyHelper->getApiKeyMessage('API key deleted - have a nice day', $apiKey);
        }

        if ($input->isInteractive()) {
            $message ??= 'Nothing changed - have a nice day';

            $io->success($message);
        }

        return 0;
    }
}
