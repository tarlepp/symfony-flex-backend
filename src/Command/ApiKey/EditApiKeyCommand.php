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
     * @var SymfonyStyle
     */
    private $io;

    /**
     * EditUserCommand constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ApiKeyResource $apiKeyResource)
    {
        parent::__construct('api-key:edit');

        $this->apiKeyResource = $apiKeyResource;

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
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        $apiKeyFound = false;

        while (!$apiKeyFound) {
            $apiKey = $this->getApiKey();

            $message = \sprintf(
                'Is this the API key \'[%s] [%s] %s\' which information you want to change?',
                $apiKey->getId(),
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $apiKeyFound = $this->io->confirm($message, false);
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
            $this->io->success([
                'API key updated - have a nice day',
                ' guid: ' . $apiKey->getId() . "\n" . 'token: ' . $apiKey->getToken(),
            ]);
        }

        return null;
    }

    /**
     * @return ApiKeyEntity
     */
    private function getApiKey(): ApiKeyEntity
    {
        $choices = [];

        /**
         * Lambda function create API key choices
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

        \array_map($iterator, $this->apiKeyResource->find([], ['token' => 'ASC']));

        return $this->apiKeyResource->findOne($this->io->choice('Which API key you want to edit?', $choices));
    }
}
