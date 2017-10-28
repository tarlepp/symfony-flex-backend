<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/EditApiKeyCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\DTO\ApiKey as ApiKeyDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Form\Type\Console\ApiKeyType;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class EditApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditApiKeyCommand extends Command
{
    /**
     * @var ApiKeyResource
     */
    private $apiKeyResource;

    /**
     * @var ApiKeyHelper
     */
    private $apiKeyHelper;

    /**
     * EditUserCommand constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     * @param ApiKeyHelper   $apiKeyHelper
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ApiKeyResource $apiKeyResource, ApiKeyHelper $apiKeyHelper)
    {
        parent::__construct('api-key:edit');

        $this->apiKeyResource = $apiKeyResource;
        $this->apiKeyHelper = $apiKeyHelper;

        $this->setDescription('Command to edit existing API key');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        // Get ApiKey
        $apiKey = $this->getApiKey($io);

        $message = 'Nothing changed - have a nice day';

        /** @var ApiKeyEntity|null $apiKey */
        if ($apiKey instanceof ApiKeyEntity) {
            $message = $this->updateApiKey($input, $output, $apiKey);
        }

        if ($input->isInteractive()) {
            $io->success($message);
        }

        return null;
    }

    /**
     * Method to fetch API key entity for editing.
     *
     * @param SymfonyStyle $io
     *
     * @return ApiKeyEntity|null
     */
    private function getApiKey($io): ?ApiKeyEntity
    {
        $apiKeyFound = false;

        while ($apiKeyFound === false) {
            $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key you want to edit?');

            if ($apiKey === null) {
                break;
            }

            $message = \sprintf(
                'Is this the API key \'[%s] [%s] %s\' which information you want to change?',
                $apiKey->getId(),
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $apiKeyFound = $io->confirm($message, false);
        }

        return $apiKey ?? null;
    }

    /**
     * Method to update specified API key via specified form.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param ApiKeyEntity    $apiKey
     *
     * @return array
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function updateApiKey(InputInterface $input, OutputInterface $output, ApiKeyEntity $apiKey): array
    {
        // Load entity to DTO
        $dtoLoaded = new ApiKeyDto();
        $dtoLoaded->load($apiKey);

        /** @var ApiKeyDto $dtoEdit */
        $dtoEdit = $this->getHelper('form')->interactUsingForm(
            ApiKeyType::class,
            $input,
            $output,
            ['data' => $dtoLoaded]
        );

        // Update API key
        $this->apiKeyResource->update($apiKey->getId(), $dtoEdit);

        $message = [
            'API key updated - have a nice day',
            ' guid: ' . $apiKey->getId() . "\n" . 'token: ' . $apiKey->getToken(),
        ];

        return $message;
    }
}
