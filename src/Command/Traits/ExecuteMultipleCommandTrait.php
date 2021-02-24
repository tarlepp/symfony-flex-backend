<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/ExecuteMultipleCommandTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\Traits;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_flip;
use function array_search;
use function array_values;

/**
 * Trait ExecuteMultipleCommandTrait
 *
 * @package App\Command\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait ExecuteMultipleCommandTrait
{
    use GetApplicationTrait;
    use SymfonyStyleTrait;

    /**
     * @var array<array-key, string>
     */
    private array $choices = [];

    /**
     * Setter method for choices to use.
     *
     * @param array<array-key, string> $choices
     */
    protected function setChoices(array $choices): void
    {
        $this->choices = $choices;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $command = $this->ask($io);

        while ($command !== null) {
            $arguments = [
                'command' => $command,
            ];

            $input = new ArrayInput($arguments);

            $cmd = $this->getApplication()->find($command);
            $outputValue = $cmd->run($input, $output);

            $command = $this->ask($io);
        }

        if ($input->isInteractive()) {
            $io->success('Have a nice day');
        }

        return $outputValue ?? 0;
    }

    /**
     * Method to ask user to make choose one of defined choices.
     */
    private function ask(SymfonyStyle $io): ?string
    {
        $index = array_search(
            $io->choice('What you want to do', array_values($this->choices)),
            array_values($this->choices),
            true
        );

        $choice = (string)array_values(array_flip($this->choices))[(int)$index];

        return $choice === '0' ? null : $choice;
    }
}
