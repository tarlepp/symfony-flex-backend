<?php
declare(strict_types=1);
/**
 * /src/Maker/RestApiMaker.php
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
 * Class RestApiMaker
 *
 * @package App\Maker
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestApiMaker implements MakerInterface
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var string
     */
    private $author;

    /**
     * @var string
     */
    private $swaggerTag;

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
        $message = \sprintf(
            'Creates necessary classes for new REST resource (%s)',
            implode(', ', ['Entity', 'Repository', 'Resource', 'Controller', 'Test classes'])
        );

        $command
            ->setDescription($message);
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
        $this->resourceName = \ucfirst($io->ask('Name of the resource'));
        $this->author = $io->ask('Author name');
        $this->swaggerTag = $io->ask('Swagger documentation tag');
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
            'resource'          => $this->resourceName,
            'controllerName'    => $this->resourceName . 'Controller',
            'entityName'        => $this->resourceName,
            'repositoryName'    => $this->resourceName . 'Repository',
            'resourceName'      => $this->resourceName . 'Resource',
            'author'            => $this->author,
            'swaggerTag'        => $this->swaggerTag,
            'routePath'         => '/' . $this->convertToSnakeCase($this->resourceName),
            'tableName'         => $this->convertToSnakeCase($this->resourceName),
        ];
    }

    /**
     * Return the array of files that should be generated into the user's project.
     *
     * For example:
     *
     *    return array(
     *        __DIR__.'/../Resources/skeleton/command/Command.tpl.php' =>
     *        'src/Command/'.$params['command_class_name'].'.php',
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
        $baseDir = __DIR__ . '/../../templates/skeleton/rest-api/';

        return \array_merge(
            [
                $baseDir . 'Controller.tpl.php' => 'src/Controller/' . $params['controllerName'] . '.php',
                $baseDir . 'Entity.tpl.php'     => 'src/Entity/' . $params['entityName'] . '.php',
                $baseDir . 'Repository.tpl.php' => 'src/Repository/' . $params['repositoryName'] . '.php',
                $baseDir . 'Resource.tpl.php'   => 'src/Resource/' . $params['resourceName'] . '.php',
            ],
            $this->getTestFiles($params, $baseDir)
        );
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
            'Then, change your REST traits in controller how you like to use those',
        ]);
    }

    /**
     * @param string $input
     *
     * @return string
     */
    private function convertToSnakeCase(string $input): string
    {
        \preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);

        /** @var array $ret */
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = $match === \strtoupper($match) ? \strtolower($match) : \lcfirst($match);
        }

        return \implode('_', $ret);
    }

    /**
     * @param array  $params
     * @param string $baseDir
     *
     * @return array
     */
    private function getTestFiles(array $params, string $baseDir): array
    {
        $foo = [
            'Functional' => [
                'ControllerTestFunctional.tpl.php' => [
                    'tests/Functional/Controller/',
                    $params['controllerName'] . 'Test.php',
                ]
            ],
            'Integration' => [
                'ControllerTestIntegration.tpl.php' => [
                    'tests/Integration/Controller/',
                    $params['controllerName'] . 'Test.php',
                ],
                'EntityTestIntegration.tpl.php' => [
                    'tests/Integration/Entity/',
                    $params['entityName'] . 'Test.php',
                ],
                'RepositoryTestIntegration.tpl.php' => [
                    'tests/Integration/Repository/',
                    $params['repositoryName'] . 'Test.php',
                ],
                'ResourceTestIntegration.tpl.php' => [
                    'tests/Integration/Resource/',
                    $params['resourceName'] . 'Test.php',
                ],
            ],
        ];

        $output = [];

        /**
         * @var string $section
         * @var array  $items
         */
        foreach ($foo as $section => $items) {
            foreach ($items as $key => $parts) {
                $output[$baseDir . $key] = \implode('', $parts);
            }
        }

        return $output;
    }
}
