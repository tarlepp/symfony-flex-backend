<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/EditApiKeyCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\ApiKey as ApiKeyDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Form\Type\Console\ApiKeyType;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EditApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditApiKeyCommand extends Command
{
    // Traits
    use SymfonyStyleTrait;

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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Get API key entity
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key you want to edit?');

        if ($apiKey instanceof ApiKeyEntity) {
            $message = $this->updateApiKey($input, $output, $apiKey);
        }

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
        }

        return null;
    }

    /**
     * Method to update specified API key via specified form.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param ApiKeyEntity    $apiKey
     *
     * @return mixed[]
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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

        return $this->apiKeyHelper->getApiKeyMessage('API key updated - have a nice day', $apiKey);
    }
}
