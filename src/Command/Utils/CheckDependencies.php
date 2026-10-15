<?php
declare(strict_types = 1);

/**
 * /src/Command/Utils/CheckDependencies.php
 */

namespace App\Command\Utils;

use App\Command\Traits\SymfonyStyleTrait;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Override;
use SplFileInfo;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Throwable;
use Traversable;
use function array_filter;
use function array_is_list;
use function array_map;
use function array_unshift;
use function count;
use function dirname;
use function in_array;
use function is_array;
use function is_string;
use function iterator_to_array;
use function json_decode;
use function max;
use function property_exists;
use function sort;
use function sprintf;
use function str_replace;
use function strlen;
use const DIRECTORY_SEPARATOR;

#[AsCommand(
    name: 'check-dependencies',
    description: 'Console command to check which vendor dependencies has updates',
)]
class CheckDependencies extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();

        $this->addOption(
            'level',
            null,
            InputOption::VALUE_REQUIRED,
            'Update level to check: latest, minor, or patch',
            'latest',
            ['latest', 'minor', 'patch'],
        );
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $level = $input->getOption('level');

        $io = $this->getSymfonyStyle($input, $output);

        if (!in_array($level, ['latest', 'minor', 'patch'], true)) {
            $io->error('The --level option must be one of: latest, minor, patch.');

            return Command::INVALID;
        }

        $io->info([
            'Starting to check dependencies...',
            match ($level) {
                'patch' => 'Checking only patch version updates',
                'minor' => 'Checking only minor version updates',
                default => 'Checking for latest version updates',
            },
        ]);

        $directories = $this->getNamespaceDirectories();

        array_unshift($directories, $this->projectDir);

        $rows = $this->determineTableRows($io, $directories, $level);

        $packageNameLength = max(
            array_map(
                static fn (array $row): int => isset($row[1]) ? strlen($row[1]) : 0,
                array_filter($rows, static fn (mixed $row): bool => !$row instanceof TableSeparator),
            ) + [0],
        );

        $style = clone Table::getStyleDefinition('box');
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($output);
        $table->setHeaders($this->getHeaders());
        $table->setRows($rows);
        $table->setStyle($style);

        $this->setTableColumnWidths($packageNameLength, $table);

        $rows === []
            ? $io->success('Good news, there is no any vendor dependency to update at this time!')
            : $table->render();

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
        // Find Composer manifests directly inside each tool group.
        $finder = new Finder()
            ->depth(1)
            ->ignoreDotFiles(true)
            ->files()
            ->name('composer.json')
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
    private function determineTableRows(SymfonyStyle $io, array $directories, string $level): array
    {
        // Initialize progress bar for process
        $progressBar = $this->getProgressBar($io, count($directories), 'Checking all vendor dependencies');

        // Initialize output rows
        $rows = [];

        $iterator = function (string $directory) use ($io, $level, $progressBar, &$rows): void {
            $progressBar->setMessage(sprintf(
                'Checking vendor dependencies: %s/composer.json',
                $directory === $this->projectDir ? '.' : str_replace($this->projectDir . '/', '', $directory),
            ));
            $progressBar->display();

            foreach ($this->processNamespacePath($directory, $level) as $row => $data) {
                $relativePath = '';

                // First row of current library
                if ($row === 0) {
                    // We want to add table separator between different libraries
                    if ($rows !== []) {
                        $rows[] = new TableSeparator();
                    }

                    $relativePath = str_replace($this->projectDir, '', $directory) . '/composer.json';
                } else {
                    $rows[] = [''];
                }

                $rows[] = $this->getPackageRow($relativePath, $data);

                if (isset($data->warning)) {
                    $rows[] = [''];
                    $rows[] = ['', '', '<fg=red>' . (string)$data->warning . '</>'];
                }

                if (!property_exists($data, 'latest')) {
                    $rows[] = [''];
                    $rows[] = [
                        '',
                        '',
                        '<fg=yellow>There is newer version, but it\'s not compatible with current setup</>',
                    ];
                }
            }

            if (count($rows) === 1) {
                $io->write("\033\143");
            }

            $progressBar->advance();
        };

        array_map($iterator, $directories);

        $progressBar->setMessage('Finished checking all vendor dependencies');
        $progressBar->finish();
        $progressBar->display();

        return $rows;
    }

    /**
     * Method to process namespace inside 'tools' directory.
     *
     * @return array<int, stdClass>
     *
     * @throws JsonException
     */
    private function processNamespacePath(string $path, string $level): array
    {
        $command = [
            'composer',
            'outdated',
            '-D',
            '-f',
            'json',
        ];

        if ($level === 'minor') {
            $command[] = '-m';
        } elseif ($level === 'patch') {
            $command[] = '-p';
        }

        $process = new Process($command, $path);
        $process->enableOutput();
        $process->run();

        $this->ensureProcessSucceeded($process);

        return $this->decodeComposerOutput($process->getOutput(), $path);
    }

    /**
     * @return list<stdClass>
     *
     * @throws JsonException
     */
    private function decodeComposerOutput(string $output, string $path): array
    {
        try {
            $decoded = json_decode($output, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonException(
                sprintf("Invalid Composer JSON in '%s': %s", $path, $exception->getMessage()),
                previous: $exception,
            );
        }

        if ($decoded === []) {
            return [];
        }

        if (!$decoded instanceof stdClass
            || !isset($decoded->installed)
            || !is_array($decoded->installed)
            || !array_is_list($decoded->installed)
        ) {
            throw new RuntimeException(sprintf(
                "Invalid Composer response in '%s': expected an object with an 'installed' package list.",
                $path,
            ));
        }

        $installed = $decoded->installed;

        foreach ($installed as $index => $package) {
            if (!$package instanceof stdClass) {
                throw new RuntimeException(sprintf(
                    "Invalid Composer response in '%s': package at index %d must be an object.",
                    $path,
                    $index,
                ));
            }

            foreach (['name', 'version'] as $field) {
                if (!isset($package->{$field}) || !is_string($package->{$field}) || $package->{$field} === '') {
                    throw new RuntimeException(sprintf(
                        "Invalid Composer response in '%s': package at index %d requires a non-empty string '%s'.",
                        $path,
                        $index,
                        $field,
                    ));
                }
            }

            foreach (['description', 'latest', 'warning'] as $field) {
                if (property_exists($package, $field)
                    && !is_string($package->{$field})
                    && !($field === 'description' && $package->{$field} === null)
                ) {
                    throw new RuntimeException(sprintf(
                        "Invalid Composer response in '%s': package at index %d has an invalid '%s' field.",
                        $path,
                        $index,
                        $field,
                    ));
                }
            }
        }

        return $installed;
    }

    private function ensureProcessSucceeded(Process $process): void
    {
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "Running command '%s' in '%s' failed with exit code %s.\nStandard output:\n%s\nError output:\n%s",
                $process->getCommandLine(),
                $process->getWorkingDirectory() ?? $this->projectDir,
                $process->getExitCode() === null ? 'unknown' : (string)$process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput(),
            ));
        }
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

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private function getPackageRow(string $relativePath, stdClass $data): array
    {
        return [
            dirname($relativePath),
            (string)$data->name,
            (string)($data->description ?? ''),
            (string)$data->version,
            (string)(property_exists($data, 'latest') ? $data->latest : '<fg=yellow>' . (string)$data->version . '</>'),
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
