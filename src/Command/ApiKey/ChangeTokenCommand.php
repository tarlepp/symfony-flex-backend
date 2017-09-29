<?php
declare(strict_types=1);
/**
 * /src/Command/ApiKey/ChangeTokenCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\Entity\ApiKey;
use App\Resource\ApiKeyResource;
use Symfony\Component\Console\Command\Command;
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
     * @throws \Symfony\Component\Console\Exception\LogicException
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
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        $apiKeyFound = false;

        while (!$apiKeyFound) {
            $apiKey = $this->apiKeyHelper->getApiKey($io, 'Which API key token you want to change?');

            $message = \sprintf(
                'Is this the API key \'[%s] [%s] %s\' which token you want to change?',
                $apiKey->getId(),
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $apiKeyFound = $io->confirm($message, false);
        }

        /** @var ApiKey $apiKey */
        $apiKey->generateToken();

        // Update API key
        $this->apiKeyResource->save($apiKey);

        if ($input->isInteractive()) {
            $io->success([
                'API key token updated - have a nice day',
                ' guid: ' . $apiKey->getId() . "\n" . 'token: ' . $apiKey->getToken(),
            ]);
        }

        return null;
    }
}
