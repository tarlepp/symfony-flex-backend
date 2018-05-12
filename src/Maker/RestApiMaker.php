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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use function array_map;
use function array_merge;
use function implode;
use function lcfirst;
use function preg_match_all;
use function sprintf;
use function strtolower;
use function strtoupper;

/**
 * Class RestApiMaker
 *
 * @package App\Maker
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestApiMaker extends AbstractMaker
{
    private const PARAM_RESOURCE_NAME = 'resourceName';
    private const PARAM_AUTHOR = 'author';
    private const PARAM_SWAGGER_TAG = 'swaggerTag';
    private const PARAM_CONTROLLER_NAME = 'controllerName';
    private const PARAM_ENTITY_NAME = 'entityName';
    private const PARAM_REPOSITORY_NAME = 'repositoryName';
    private const PARAM_DTO = 'DTO';
    private const PARAM_ENTITY = 'Entity';
    private const PARAM_REPOSITORY = 'Repository';
    private const PARAM_RESOURCE = 'Resource';
    private const PARAM_CONTROLLER = 'Controller';
    private const KEY_SUFFIX = 'suffix';
    private const KEY_NAMESPACE = 'namespace';
    private const KEY_TEMPLATE = 'template';
    private const KEY_NAME = 'name';
    private const KEY_PARAMETERS = 'parameters';

    /**
     * @var string[]
     */
    private $createdFiles = [];

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
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command->setName(self::getCommandName());

        $message = sprintf(
            'Creates necessary classes for new REST resource (%s)',
            implode(
                ', ',
                [
                    self::PARAM_ENTITY,
                    self::PARAM_REPOSITORY,
                    self::PARAM_RESOURCE,
                    self::PARAM_CONTROLLER,
                    'Test classes',
                ]
            )
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
     * Called after normal code generation: allows you to do anything.
     *
     * @param InputInterface $input
     * @param ConsoleStyle   $io
     * @param Generator      $generator
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $parameters = $this->getParameters($input);

        $processFile = static function (array $input) use ($generator, $parameters): string {
            $details = $generator->createClassNameDetails(
                $input[self::KEY_NAME],
                $input[self::KEY_NAMESPACE],
                $input[self::KEY_SUFFIX]
            );

            return $generator->generateClass($details->getFullName(), $input[self::KEY_TEMPLATE], $parameters);
        };

        $this->setCreatedFiles(array_map($processFile, $this->getFiles($parameters)));

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Customize your entity.',
            'Then, change your REST traits in controller how you like to use those',
        ]);
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

        return [
            'resource' => $resourceName,
            'controllerName' => $resourceName . self::PARAM_CONTROLLER,
            'entityName' => $resourceName,
            'repositoryName' => $resourceName . self::PARAM_REPOSITORY,
            'resourceName' => $resourceName . self::PARAM_RESOURCE,
            'author' => $author,
            'swaggerTag' => $swaggerTag,
            'routePath' => '/' . $this->convertToSnakeCase($resourceName),
            'tableName' => $this->convertToSnakeCase($resourceName),
        ];
    }

    /**
     * @return string[]
     */
    public function getCreatedFiles(): array
    {
        return $this->createdFiles;
    }

    /**
     * @param string[] $createdFiles
     *
     * @return RestApiMaker
     */
    public function setCreatedFiles(array $createdFiles): self
    {
        $this->createdFiles = $createdFiles;

        return $this;
    }

    /**
     * @param mixed[] $params
     *
     * @return mixed[]
     */
    public function getFiles(array $params): array
    {
        $baseDir = __DIR__ . '/../../templates/skeleton/rest-api/';

        return array_merge(
            [
                [
                    self::KEY_NAME => $params[self::PARAM_CONTROLLER_NAME],
                    self::KEY_NAMESPACE => self::PARAM_CONTROLLER,
                    self::KEY_TEMPLATE => $baseDir . 'Controller.tpl.php',
                    self::KEY_SUFFIX => '',
                ],
                [
                    self::KEY_NAME => $params[self::PARAM_ENTITY_NAME],
                    self::KEY_NAMESPACE => self::PARAM_ENTITY,
                    self::KEY_TEMPLATE => $baseDir . 'Entity.tpl.php',
                    self::KEY_SUFFIX => '',
                ],
                [
                    self::KEY_NAME => $params[self::PARAM_ENTITY_NAME],
                    self::KEY_NAMESPACE => self::PARAM_DTO,
                    self::KEY_TEMPLATE => $baseDir . 'Dto.tpl.php',
                    self::KEY_SUFFIX => '',
                ],
                [
                    self::KEY_NAME => $params[self::PARAM_REPOSITORY_NAME],
                    self::KEY_NAMESPACE => self::PARAM_REPOSITORY,
                    self::KEY_TEMPLATE => $baseDir . 'Repository.tpl.php',
                    self::KEY_PARAMETERS => $params,
                    self::KEY_SUFFIX => '',
                ],
                [
                    self::KEY_NAME => $params[self::PARAM_RESOURCE_NAME],
                    self::KEY_NAMESPACE => self::PARAM_RESOURCE,
                    self::KEY_TEMPLATE => $baseDir . 'Resource.tpl.php',
                    self::KEY_SUFFIX => '',
                ],
            ],
            $this->getTestFiles($params, $baseDir)
        );
    }

    /**
     * @param string $input
     *
     * @return string
     */
    private function convertToSnakeCase(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);

        /** @var array $ret */
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    /**
     * @param mixed[] $params
     * @param string  $baseDir
     *
     * @return mixed[]
     */
    private function getTestFiles(array $params, string $baseDir): array
    {
        return [
            [
                self::KEY_NAME => $params[self::PARAM_CONTROLLER_NAME],
                self::KEY_NAMESPACE => 'Tests\\Functional\\Controller\\',
                self::KEY_TEMPLATE => $baseDir . 'ControllerTestFunctional.tpl.php',
                self::KEY_SUFFIX => 'Test',
            ],
            [
                self::KEY_NAME => $params[self::PARAM_CONTROLLER_NAME],
                self::KEY_NAMESPACE => 'Tests\\Integration\\Controller\\',
                self::KEY_TEMPLATE => $baseDir . 'ControllerTestIntegration.tpl.php',
                self::KEY_SUFFIX => 'Test',
            ],
            [
                self::KEY_NAME => $params[self::PARAM_ENTITY_NAME],
                self::KEY_NAMESPACE => 'Tests\\Integration\\DTO\\',
                self::KEY_TEMPLATE => $baseDir . 'DtoTestIntegration.tpl.php',
                self::KEY_SUFFIX => 'Test',
            ],
            [
                self::KEY_NAME => $params[self::PARAM_ENTITY_NAME],
                self::KEY_NAMESPACE => 'Tests\\Integration\\Entity\\',
                self::KEY_TEMPLATE => $baseDir . 'EntityTestIntegration.tpl.php',
                self::KEY_SUFFIX => 'Test',
            ],
            [
                self::KEY_NAME => $params[self::PARAM_REPOSITORY_NAME],
                self::KEY_NAMESPACE => 'Tests\\Integration\\Repository\\',
                self::KEY_TEMPLATE => $baseDir . 'RepositoryTestIntegration.tpl.php',
                self::KEY_SUFFIX => 'Test',
            ],
            [
                self::KEY_NAME => $params[self::PARAM_RESOURCE_NAME],
                self::KEY_NAMESPACE => 'Tests\\Integration\\Resource\\',
                self::KEY_TEMPLATE => $baseDir . 'ResourceTestIntegration.tpl.php',
                self::KEY_SUFFIX => 'Test',
            ],
        ];
    }
}
