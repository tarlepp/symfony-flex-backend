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
     * @var SymfonyStyle
     */
    private $io;

    /**
     * ChangeTokenCommand constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ApiKeyResource $apiKeyResource)
    {
        parent::__construct('api-key:change-token');

        $this->apiKeyResource = $apiKeyResource;

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
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        $apiKeyFound = false;

        while (!$apiKeyFound) {
            $apiKey = $this->getApiKey();

            $message = \sprintf(
                'Is this the API key \'[%s] [%s] %s\' which token you want to change?',
                $apiKey->getId(),
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $apiKeyFound = $this->io->confirm($message, false);
        }

        /** @var ApiKey $apiKey */
        $apiKey->generateToken();

        // Update API key
        $this->apiKeyResource->save($apiKey);

        if ($input->isInteractive()) {
            $this->io->success([
                'API key token updated - have a nice day',
                ' guid: ' . $apiKey->getId() . "\n" . 'token: ' . $apiKey->getToken(),
            ]);
        }

        return null;
    }

    /**
     * @return ApiKey
     */
    private function getApiKey(): ApiKey
    {
        $choices = [];

        /**
         * Lambda function create API key choices
         *
         * @param ApiKey $apiKey
         */
        $iterator = function (ApiKey $apiKey) use (&$choices): void {
            $message = \sprintf(
                '[%s] %s',
                $apiKey->getToken(),
                $apiKey->getDescription()
            );

            $choices[$apiKey->getId()] = $message;
        };

        \array_map($iterator, $this->apiKeyResource->find([], ['token' => 'ASC']));

        return $this->apiKeyResource->findOne($this->io->choice('Which API key token you want to change?', $choices));
    }
}
