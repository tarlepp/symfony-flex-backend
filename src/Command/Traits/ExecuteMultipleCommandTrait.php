<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/ExecuteMultipleCommandTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait ExecuteMultipleCommandTrait
{
    use GetApplicationTrait;

    /**
     * @var array<int|string, string>
     */
    private array $choices = [];

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    /**
     * Setter method for choices to use.
     *
     * @param array<int|string, string> $choices
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
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");
        $command = $this->ask();

        while ($command !== null) {
            $arguments = [
                'command' => $command,
            ];

            $input = new ArrayInput($arguments);

            $cmd = $this->getApplication()->find($command);
            $outputValue = $cmd->run($input, $output);

            $command = $this->ask();
        }

        if ($input->isInteractive()) {
            $this->io->success('Have a nice day');
        }

        return $outputValue ?? 0;
    }

    /**
     * Method to ask user to make choose one of defined choices.
     */
    private function ask(): ?string
    {
        $index = array_search(
            $this->io->choice('What you want to do', array_values($this->choices)),
            array_values($this->choices),
            true
        );

        $choice = (string)array_values(array_flip($this->choices))[(int)$index];

        return $choice === '0' ? null : $choice;
    }
}
