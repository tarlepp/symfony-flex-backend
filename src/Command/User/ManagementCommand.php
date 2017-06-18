<?php
declare(strict_types=1);
/**
 * /src/Command/User/ManagementCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ManagementCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ManagementCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var array
     */
    private static $choices = [
        'user:create'       => 'Create user',
        'user:create-group' => 'Create user group',
        'user:edit'         => 'Edit user',
        'user:edit-group'   => 'Edit user group',
        'user:list'         => 'List users',
        'user:list-groups'  => 'List user groups',
        false               => 'Exit',
    ];

    /**
     * ListUserGroupsCommand constructor.
     *
     * @param null|string    $name
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct($name = null)
    {
        parent::__construct('user:management');

        $this->setDescription('Console command to manage users and user groups');
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

        $command = $this->ask();

        while ($command) {
            $arguments = [
                'command'   => $command,
            ];

            $input = new ArrayInput($arguments);

            $cmd = $this->getApplication()->find($command);
            $cmd->run($input, $output);

            $command = $this->ask();
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

        return \array_values(\array_flip(self::$choices))[$index];
    }
}
