<?php
declare(strict_types = 1);
/**
 * /src/Command/Utils/CheckDependencies.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\Utils;

use App\Command\Traits\SymfonyStyleTrait;
use InvalidArgumentException;
use JsonException;
use LogicException;
use SplFileInfo;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Throwable;
use Traversable;
use function array_map;
use function array_unshift;
use function count;
use function dirname;
use function implode;
use function is_array;
use function iterator_to_array;
use function sort;
use function sprintf;
use function str_replace;

/**
 * Class CheckDependencies
 *
 * @package App\Command\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CheckDependencies extends Command
{
    use SymfonyStyleTrait;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    public function __construct(private string $projectDir)
    {
        parent::__construct('check-dependencies');

        $this->setDescription('Console command to check which vendor dependencies has updates');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = $this->getSymfonyStyle($input, $output);

        $directories = $this->getNamespaceDirectories();

        array_unshift($directories, $this->projectDir);

        $rows = $this->determineTableRows($directories);

        $headers = [
            'Path',
            'Dependency',
            'Description',
            'Version',
            'New version',
        ];

        $style = clone Table::getStyleDefinition('box');
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);
        $table->setColumnMaxWidth(2, 80);
        $table->setColumnMaxWidth(3, 10);
        $table->setColumnMaxWidth(4, 11);

        count($rows)
            ? $table->render()
            : $this->io->success('Good news, there is not any vendor dependency to update at this time!');

        return 0;
    }

    /**
     * Method to determine all namespace directories under 'tools' directory.
     *
     * @return array<int, string>
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    private function getNamespaceDirectories(): array
    {
        // Find all main namespace directories under 'tools' directory
        $finder = (new Finder())
            ->depth(1)
            ->ignoreDotFiles(true)
            ->directories()
            ->in($this->projectDir . DIRECTORY_SEPARATOR . 'tools/');

        /**
         * Closure to return pure path from current SplFileInfo object.
         *
         * @param SplFileInfo $fileInfo
         *
         * @return string
         */
        $closure = static fn (SplFileInfo $fileInfo): string => $fileInfo->getPath();

        /** @var Traversable $iterator */
        $iterator = $finder->getIterator();

        // Determine namespace directories
        $directories = array_map($closure, iterator_to_array($iterator));

        sort($directories);

        return $directories;
    }

    /**
     * Method to determine table rows.
     *
     * @param array<int, string> $directories
     *
     * @return array<int, array<int, string>|TableSeparator>
     *
     * @throws JsonException
     */
    private function determineTableRows(array $directories): array
    {
        // Initialize progress bar for process
        $progressBar = $this->getProgressBar(count($directories), 'Checking all vendor dependencies');

        // Initialize output rows
        $rows = [];

        /**
         * Closure to process each vendor directory and check if there is libraries to be updated.
         *
         * @param string $directory
         */
        $iterator = function (string $directory) use ($progressBar, &$rows): void {
            foreach ($this->processNamespacePath($directory) as $row => $data) {
                $relativePath = '';

                // First row of current library
                if ($row === 0) {
                    // We want to add table separator between different libraries
                    if (count($rows) > 0) {
                        $rows[] = new TableSeparator();
                    }

                    $relativePath = str_replace($this->projectDir, '', $directory) . '/composer.json';
                } else {
                    $rows[] = [''];
                }

                $rows[] = [dirname($relativePath), $data->name, $data->description, $data->version, $data->latest];

                if (isset($data->warning)) {
                    $rows[] = [''];
                    $rows[] = ['', '', '<fg=red>' . $data->warning . '</>'];
                }
            }

            $progressBar->advance();
        };

        array_map($iterator, $directories);

        return $rows;
    }

    /**
     * Method to process namespace inside 'tools' directory.
     *
     * @return array<int, stdClass>
     *
     * @throws JsonException
     */
    private function processNamespacePath(string $path): array
    {
        $command = [
            'composer',
            'outdated',
            '-D',
            '-f',
            'json',
        ];

        $process = new Process($command, $path);
        $process->enableOutput();
        $process->run();

        if ($process->getErrorOutput() !== '' && !($process->getExitCode() === 0 || $process->getExitCode() === null)) {
            $message = sprintf(
                "Running command '%s' failed with error message:\n%s",
                implode(' ', $command),
                $process->getErrorOutput()
            );

            throw new RuntimeException($message);
        }

        /** @var stdClass $decoded */
        $decoded = json_decode($process->getOutput(), false, 512, JSON_THROW_ON_ERROR);

        /** @var array<int, stdClass>|string|null $installed */
        $installed = $decoded->installed;

        return is_array($installed) ? $installed : [];
    }

    /**
     * Helper method to get progress bar for console.
     */
    private function getProgressBar(int $steps, string $message): ProgressBar
    {
        $format = '
 %message%
 %current%/%max% [%bar%] %percent:3s%%
 Time elapsed:   %elapsed:-6s%
 Time remaining: %remaining:-6s%
 Time estimated: %estimated:-6s%
 Memory usage:   %memory:-6s%
';

        $progress = $this->io->createProgressBar($steps);
        $progress->setFormat($format);
        $progress->setMessage($message);

        return $progress;
    }
}
