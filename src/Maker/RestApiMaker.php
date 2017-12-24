<?php
declare(strict_types=1);
/**
 * /src/Maker/RestMaker.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class RestMaker
 *
 * @package App\Maker
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestApiMaker implements MakerInterface
{
    /**
     * @var string
     */
    private $resource;

    /**
     * Return the command name for your maker (e.g. make:report).
     *
     * @return string
     */
    public static function getCommandName(): string
    {
        return 'make:rest-api';
    }

    /**
     * Configure the command: set description, input arguments, options, etc.
     *
     * By default, all arguments will be asked interactively. If you want
     * to avoid that, use the $inputConfig->setArgumentAsNonInteractive() method.
     *
     * @param Command            $command
     * @param InputConfiguration $inputConfig
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates necessary classes for new REST resource (Entity, Repository, Resource, etc.)');
    }

    /**
     * Configure any library dependencies that your maker requires.
     *
     * @param DependencyBuilder $dependencies
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // TODO: Implement configureDependencies() method.
    }

    /**
     * If necessary, you can use this method to interactively ask the user for input.
     *
     * @param InputInterface $input
     * @param ConsoleStyle   $io
     * @param Command        $command
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $this->resource = $io->ask('Name of the resource');
    }

    /**
     * Return an array of variables that will be made available to the
     * template files returned from getFiles().
     *
     * @param InputInterface $input
     *
     * @return array
     */
    public function getParameters(InputInterface $input): array
    {
        return [
            'resource' => $this->resource,
        ];
    }

    /**
     * Return the array of files that should be generated into the user's project.
     *
     * For example:
     *
     *    return array(
     *        __DIR__.'/../Resources/skeleton/command/Command.tpl.php' => 'src/Command/'.$params['command_class_name'].'.php',
     *    );
     *
     * These files are parsed as PHP.
     *
     * @param array $params The parameters returned from getParameters()
     *
     * @return array
     */
    public function getFiles(array $params): array
    {
        return [];
    }

    /**
     * An opportunity to write a nice message after generation finishes.
     *
     * @param array        $params
     * @param ConsoleStyle $io
     */
    public function writeNextStepsMessage(array $params, ConsoleStyle $io): void
    {
        $io->text([
            'Next: Customize your entity.',
            'Then, configure the "guard" key on your firewall to use it.',
        ]);
    }
}
