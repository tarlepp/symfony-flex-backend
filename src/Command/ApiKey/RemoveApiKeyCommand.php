<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/RemoveApiKeyCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\ApiKey;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class RemoveApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RemoveApiKeyCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private ApiKeyResource $apiKeyResource,
        private ApiKeyHelper $apiKeyHelper,
    ) {
        parent::__construct('api-key:remove');

        $this->setDescription('Console command to remove existing API key');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key you want to remove?');
        $message = $apiKey instanceof ApiKey ? $this->delete($apiKey) : null;

        if ($input->isInteractive()) {
            $io->success($message ?? ['Nothing changed - have a nice day']);
        }

        return 0;
    }

    /**
     * @return array<int, string>
     *
     * @throws Throwable
     */
    private function delete(ApiKey $apiKey): array
    {
        $this->apiKeyResource->delete($apiKey->getId());

        return $this->apiKeyHelper->getApiKeyMessage('API key deleted - have a nice day', $apiKey);
    }
}
