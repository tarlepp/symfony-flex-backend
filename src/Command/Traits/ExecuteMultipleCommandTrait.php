<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/ExecuteMultipleCommandTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\Traits;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait ExecuteMultipleCommandTrait
 *
 * @package App\Command\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method Application getApplication()
 */
trait ExecuteMultipleCommandTrait
{
    /**
     * @var array
     */
    protected static $choices = [];

    /**
     * @var SymfonyStyle
     */
    private $io;

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
                'command' => $command,
            ];

            $input = new ArrayInput($arguments);

            $cmd = $this->getApplication()->find((string)$command);
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
            $this->io->choice('What you want to do', \array_values(static::$choices)),
            \array_values(static::$choices),
            true
        );

        return \array_values(\array_flip(static::$choices))[(int)$index];
    }
}
