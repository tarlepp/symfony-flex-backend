<?php
declare(strict_types = 1);
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
    private const PARAM_RESOURCE_NAME = 'resourceName';
    private const PARAM_AUTHOR = 'author';
    private const PARAM_SWAGGER_TAG = 'swaggerTag';
    private const PARAM_CONTROLLER_NAME = 'controllerName';
    private const PARAM_ENTITY_NAME = 'entityName';
    private const PARAM_REPOSITORY_NAME = 'repositoryName';

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
                self::PARAM_RESOURCE_NAME,
                InputArgument::OPTIONAL,
                'Name of the resource (e.g. <fg=yellow>Book</>)'
            )
            ->addArgument(
                self::PARAM_AUTHOR,
                InputArgument::OPTIONAL,
                'Author name (e.g. <fg=yellow>TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com</>)'
            )
            ->addArgument(
                self::PARAM_SWAGGER_TAG,
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
     * @return mixed[]
     *
     * @throws \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function getParameters(InputInterface $input): array
    {
        $resourceName = Str::asClassName($input->getArgument(self::PARAM_RESOURCE_NAME));
        $author = $input->getArgument(self::PARAM_AUTHOR);
        $swaggerTag = $input->getArgument(self::PARAM_SWAGGER_TAG);

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
     * @param mixed[] $params The parameters returned from getParameters()
     *
     * @return mixed[]
     */
    public function getFiles(array $params): array
    {
        $baseDir = __DIR__ . '/../../templates/skeleton/rest-api/';

        return \array_merge(
            [
                $baseDir . 'Controller.tpl.php' => 'src/Controller/' . $params[self::PARAM_CONTROLLER_NAME] . '.php',
                $baseDir . 'Dto.tpl.php'        => 'src/DTO/' . $params[self::PARAM_ENTITY_NAME] . '.php',
                $baseDir . 'Entity.tpl.php'     => 'src/Entity/' . $params[self::PARAM_ENTITY_NAME] . '.php',
                $baseDir . 'Repository.tpl.php' => 'src/Repository/' . $params[self::PARAM_REPOSITORY_NAME] . '.php',
                $baseDir . 'Resource.tpl.php'   => 'src/Resource/' . $params[self::PARAM_RESOURCE_NAME] . '.php',
            ],
            $this->getTestFiles($params, $baseDir)
        );
    }

    /**
     * An opportunity to write a nice message after generation finishes.
     *
     * @param mixed[]      $params
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
     * @param mixed[] $params
     * @param string  $baseDir
     *
     * @return mixed[]
     */
    private function getTestFiles(array $params, string $baseDir): array
    {
        $suffix = 'Test.php';

        $tests = [
            'Functional' => [
                'ControllerTestFunctional.tpl.php' => [
                    'tests/Functional/Controller/',
                    $params[self::PARAM_CONTROLLER_NAME] . $suffix,
                ]
            ],
            'Integration' => [
                'ControllerTestIntegration.tpl.php' => [
                    'tests/Integration/Controller/',
                    $params[self::PARAM_CONTROLLER_NAME] . $suffix,
                ],
                'DtoTestIntegration.tpl.php' => [
                    'tests/Integration/DTO/',
                    $params[self::PARAM_ENTITY_NAME] . $suffix,
                ],
                'EntityTestIntegration.tpl.php' => [
                    'tests/Integration/Entity/',
                    $params[self::PARAM_ENTITY_NAME] . $suffix,
                ],
                'RepositoryTestIntegration.tpl.php' => [
                    'tests/Integration/Repository/',
                    $params[self::PARAM_REPOSITORY_NAME] . $suffix,
                ],
                'ResourceTestIntegration.tpl.php' => [
                    'tests/Integration/Resource/',
                    $params[self::PARAM_RESOURCE_NAME] . $suffix,
                ],
            ],
        ];

        $output = [];

        /**
         * @var array $items
         */
        foreach ($tests as $items) {
            foreach ($items as $key => $parts) {
                $output[$baseDir . $key] = \implode('', $parts);
            }
        }

        return $output;
    }
}
