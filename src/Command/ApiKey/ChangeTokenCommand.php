<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ChangeTokenCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\ApiKey;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: self::NAME,
    description: 'Command to change token for existing API key',
)]
class ChangeTokenCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'api-key:change-token';

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key token you want to change?');
        $message = $apiKey instanceof ApiKey ? $this->changeToken($apiKey) : null;

        if ($input->isInteractive()) {
            $io->success($message ?? ['Nothing changed - have a nice day']);
        }

        return 0;
    }

    /**
     * Method to change API key token.
     *
     * @return array<int, string>
     *
     * @throws Throwable
     */
    private function changeToken(ApiKey $apiKey): array
    {
        // Generate new token for API key
        $apiKey->generateToken();

        // Update API key
        $this->apiKeyResource->save($apiKey);

        return $this->apiKeyHelper->getApiKeyMessage('API key token updated - have a nice day', $apiKey);
    }
}
