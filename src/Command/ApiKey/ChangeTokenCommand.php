<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ChangeTokenCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\Entity\ApiKey as ApiKeyEntity;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ChangeTokenCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ChangeTokenCommand extends Command
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
     * ChangeTokenCommand constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     * @param ApiKeyHelper   $apiKeyHelper
     *
     * @throws LogicException
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
     * Executes the current command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        // Get API key entity
        $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key token you want to change?');

        if ($apiKey instanceof ApiKeyEntity) {
            $message = $this->changeApiKeyToken($apiKey);
        }

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
        }

        return null;
    }

    /**
     * Method to change API key token.
     *
     * @param ApiKeyEntity $apiKey
     *
     * @return mixed[]
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
