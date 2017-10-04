<?php
declare(strict_types=1);
/**
 * /src/Command/ApiKey/ApiKeyManagementCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ApiKeyManagementCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyManagementCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var array
     */
    private static $choices = [
        'api-key:create'        => 'Create API key',
        'api-key:edit'          => 'Edit API key',
        'api-key:change-token'  => 'Change API key token',
        'api-key:remove'        => 'Remove API key',
        'api-key:list'          => 'List API keys',
        false                   => 'Exit',
    ];

    /**
     * ManagementCommand constructor.
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct()
    {
        parent::__construct('api-key:management');

        $this->setDescription('Console command to manage API keys');
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
     * @throws \Exception
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        while ($command = $this->ask()) {
            $arguments = [
                'command'   => $command,
            ];

            $input = new ArrayInput($arguments);

            $cmd = $this->getApplication()->find($command);
            $cmd->run($input, $output);
        }

        if ($input->isInteractive()) {
            $this->io->success('Have a nice day');
        }

        return null;
    }

    /**
     * @return string|boolean
     */
    private function ask()
    {
        $index = \array_search(
            $this->io->choice('What you want to do', \array_values(self::$choices)),
            \array_values(self::$choices),
            true
        );

        return \array_values(\array_flip(self::$choices))[(int)$index];
    }
}
