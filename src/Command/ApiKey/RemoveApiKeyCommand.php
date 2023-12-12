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
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
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
#[AsCommand(
    name: self::NAME,
    description: 'Console command to remove existing API key',
)]
class RemoveApiKeyCommand extends Command
{
    use SymfonyStyleTrait;

    final public const NAME = 'api-key:remove';

    public function __construct(
        private readonly ApiKeyResource $apiKeyResource,
        private readonly ApiKeyHelper $apiKeyHelper,
    ) {
        parent::__construct();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
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
