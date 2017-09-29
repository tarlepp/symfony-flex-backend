<?php
declare(strict_types=1);
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

        $apiKeyFound = false;

        while (!$apiKeyFound) {
            $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key you want to edit?');

            $message = \sprintf(
                'Is this the API key \'[%s] [%s] %s\' which information you want to change?',
                $apiKey->getId(),
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $apiKeyFound = $io->confirm($message, false);
        }

        /** @var ApiKeyEntity $apiKey */

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

        // Update user
        $this->apiKeyResource->update($apiKey->getId(), $dtoEdit);

        if ($input->isInteractive()) {
            $io->success([
                'API key updated - have a nice day',
                ' guid: ' . $apiKey->getId() . "\n" . 'token: ' . $apiKey->getToken(),
            ]);
        }

        return null;
    }
}
