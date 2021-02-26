<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/EditApiKeyCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\ApiKey\ApiKey as ApiKeyDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Form\Type\Console\ApiKeyType;
use App\Resource\ApiKeyResource;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class EditApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EditApiKeyCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private ApiKeyResource $apiKeyResource,
        private ApiKeyHelper $apiKeyHelper,
    ) {
        parent::__construct('api-key:edit');

        $this->setDescription('Command to edit existing API key');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key you want to edit?');
        $message = $apiKey instanceof ApiKeyEntity ? $this->updateApiKey($input, $output, $apiKey) : null;

        if ($input->isInteractive()) {
            $io->success($message ?? ['Nothing changed - have a nice day']);
        }

        return 0;
    }

    /**
     * Method to update specified API key via specified form.
     *
     * @return array<int, string>
     *
     * @throws Throwable
     */
    private function updateApiKey(InputInterface $input, OutputInterface $output, ApiKeyEntity $apiKey): array
    {
        // Load entity to DTO
        $dtoLoaded = new ApiKeyDto();
        $dtoLoaded->load($apiKey);

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var ApiKeyDto $dtoEdit */
        $dtoEdit = $helper->interactUsingForm(ApiKeyType::class, $input, $output, ['data' => $dtoLoaded]);

        // Patch API key
        $this->apiKeyResource->patch($apiKey->getId(), $dtoEdit);

        return $this->apiKeyHelper->getApiKeyMessage('API key updated - have a nice day', $apiKey);
    }
}
