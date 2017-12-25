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
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setDescription($message)
            ->addArgument(
                'resourceName',
                InputArgument::OPTIONAL,
                'Name of the resource (e.g. <fg=yellow>Book</>)'
            )
            ->addArgument(
                'author',
                InputArgument::OPTIONAL,
                'Author name (e.g. <fg=yellow>TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com</>)'
            )
            ->addArgument(
                'swaggerTag',
                InputArgument::OPTIONAL,
                'Swagger documentation tag (e.g. <fg=yellow>Library</>)'
            )
            ->setHelp($message . "\n\n" . self::getCommandName() . ' [<resourceName>] [<author>] [<swaggerTag>]');
    }

    /**
     * Configure any library dependencies that your maker requires.
     *
     * @param DependencyBuilder $dependencies
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
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
    }

    /**
     * Return an array of variables that will be made available to the
     * template files returned from getFiles().
     *
     * @param InputInterface $input
     *
     * @return array
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function getParameters(InputInterface $input): array
    {
        $resourceName = Str::asClassName($input->getArgument('resourceName'));
        $author = $input->getArgument('author');
        $swaggerTag = $input->getArgument('swaggerTag');

        Validator::validateClassName($resourceName);

        return [
            'resource'          => $resourceName,
            'controllerName'    => $resourceName . 'Controller',
            'entityName'        => $resourceName,
            'repositoryName'    => $resourceName . 'Repository',
            'resourceName'      => $resourceName . 'Resource',
            'author'            => $author,
            'swaggerTag'        => $swaggerTag,
            'routePath'         => '/' . $this->convertToSnakeCase($resourceName),
            'tableName'         => $this->convertToSnakeCase($resourceName),
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
        $tests = [
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
        foreach ($tests as $section => $items) {
            foreach ($items as $key => $parts) {
                $output[$baseDir . $key] = \implode('', $parts);
            }
        }

        return $output;
    }
}
