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
use function array_filter;
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
use function strlen;

/**
 * Class CheckDependencies
 *
 * @package App\Command\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CheckDependencies extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private string $projectDir,
    ) {
        parent::__construct('check-dependencies');

        $this->setDescription('Console command to check which vendor dependencies has updates');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $io->info('Starting to check dependencies...');

        $directories = $this->getNamespaceDirectories();

        array_unshift($directories, $this->projectDir);

        $rows = $this->determineTableRows($io, $directories);

        /**
         * @psalm-suppress RedundantCastGivenDocblockType
         * @psalm-suppress ArgumentTypeCoercion
         */
        $packageNameLength = (int)max(
            array_map(
                static fn (array $row): int => isset($row[1]) ? strlen($row[1]) : 0,
                array_filter($rows, static fn (mixed $row): bool => !$row instanceof TableSeparator)
            ) + [0]
        );

        $style = clone Table::getStyleDefinition('box');
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($output);
        $table->setHeaders($this->getHeaders());
        $table->setRows($rows);
        $table->setStyle($style);

        $this->setTableColumnWidths($packageNameLength, $table);

        count($rows)
            ? $table->render()
            : $io->success('Good news, there is not any vendor dependency to update at this time!');

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

        $closure = static fn (SplFileInfo $fileInfo): string => $fileInfo->getPath();

        /** @var Traversable<SplFileInfo> $iterator */
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
     * @psalm-return array<int, array<int, string>|TableSeparator>
     *
     * @throws JsonException
     */
    private function determineTableRows(SymfonyStyle $io, array $directories): array
    {
        // Initialize progress bar for process
        $progressBar = $this->getProgressBar($io, count($directories), 'Checking all vendor dependencies');

        // Initialize output rows
        $rows = [];

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
        $decoded = json_decode($process->getOutput(), flags: JSON_THROW_ON_ERROR);

        /** @var array<int, stdClass>|string|null $installed */
        $installed = $decoded->installed;

        return is_array($installed) ? $installed : [];
    }

    /**
     * Helper method to get progress bar for console.
     */
    private function getProgressBar(SymfonyStyle $io, int $steps, string $message): ProgressBar
    {
        $format = '
 %message%
 %current%/%max% [%bar%] %percent:3s%%
 Time elapsed:   %elapsed:-6s%
 Time remaining: %remaining:-6s%
 Time estimated: %estimated:-6s%
 Memory usage:   %memory:-6s%
';

        $progress = $io->createProgressBar($steps);
        $progress->setFormat($format);
        $progress->setMessage($message);

        return $progress;
    }

    /**
     * @return array<int, string>
     */
    private function getHeaders(): array
    {
        return [
            'Path',
            'Dependency',
            'Description',
            'Version',
            'New version',
        ];
    }

    private function setTableColumnWidths(int $packageNameLength, Table $table): void
    {
        $widths = [
            23,
            $packageNameLength,
            95 - $packageNameLength,
            10,
            11,
        ];

        foreach ($widths as $columnIndex => $width) {
            $table->setColumnWidth($columnIndex, $width);
            $table->setColumnMaxWidth($columnIndex, $width);
        }
    }
}
