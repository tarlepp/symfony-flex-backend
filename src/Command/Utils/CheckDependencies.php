<?php
declare(strict_types = 1);

/**
 * /src/Command/Utils/CheckDependencies.php
 */

namespace App\Command\Utils;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\Command\OutdatedPackage;
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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;
use Traversable;
use function array_filter;
use function array_is_list;
use function array_map;
use function array_unshift;
use function count;
use function filter_var;
use function in_array;
use function is_array;
use function is_finite;
use function is_string;
use function iterator_to_array;
use function json_decode;
use function max;
use function min;
use function property_exists;
use function sort;
use function sprintf;
use function strlen;
use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_FLOAT;

#[AsCommand(
    name: 'check-dependencies',
    description: 'Check which vendor dependencies have updates',
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

        $this->addOption(
            'timeout',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum time in seconds for each dependency group (must be greater than zero)',
            '60',
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
        $timeout = filter_var($input->getOption('timeout'), FILTER_VALIDATE_FLOAT);
        $status = Command::INVALID;

        $io = $this->getSymfonyStyle($input, $output, clearScreen: false);

        if (!in_array($level, ['latest', 'minor', 'patch'], true)) {
            $io->error('The --level option must be one of: latest, minor, patch.');
        } elseif ($timeout === false || $timeout <= 0 || !is_finite($timeout)) {
            $io->error('The --timeout option must be positive, finite seconds.');
        } else {
            $this->checkDependencies($io, $output, $level, $timeout);

            $status = Command::SUCCESS;
        }

        return $status;
    }

    private function checkDependencies(
        SymfonyStyle $io,
        OutputInterface $output,
        string $level,
        float $timeout,
    ): void {
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

        $rows = $this->determineTableRows($io, $directories, $level, $timeout);

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

        if ($rows === []) {
            $io->success('Good news, no vendor dependency updates were found for the selected level!');
        } else {
            $table->render();
            $io->warning('Vendor dependency updates are available for the selected level. This check is informational.');
        }
    }

    /**
     * Method to determine all namespace directories under 'tools' directory.
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     *
     * @return array<int, string>
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
     * @throws JsonException
     *
     * @psalm-return array<int, array<int, string>|TableSeparator>
     */
    private function determineTableRows(SymfonyStyle $io, array $directories, string $level, float $timeout): array
    {
        // Initialize progress bar for process
        $progressBar = $this->getProgressBar($io, count($directories), 'Checking all vendor dependencies');

        // Initialize output rows
        $rows = [];

        $iterator = function (string $directory) use ($level, $timeout, $progressBar, &$rows): void {
            $groupPath = $this->getGroupPath($directory);

            $progressBar->setMessage(sprintf(
                'Checking vendor dependencies: %s/composer.json',
                $groupPath,
            ));
            $progressBar->display();

            foreach ($this->processNamespacePath($directory, $level, $timeout) as $row => $data) {
                $relativePath = '';

                // First row of current library
                if ($row === 0) {
                    // We want to add table separator between different libraries
                    if ($rows !== []) {
                        $rows[] = new TableSeparator();
                    }

                    $relativePath = $groupPath;
                } else {
                    $rows[] = [''];
                }

                $rows[] = $this->getPackageRow($relativePath, $data);

                if (isset($data->warning)) {
                    $rows[] = [''];
                    $rows[] = ['', '', '<fg=red>' . $data->warning . '</>'];
                }

                if ($data->latest === null) {
                    $rows[] = [''];
                    $rows[] = [
                        '',
                        '',
                        '<fg=yellow>There is newer version, but it\'s not compatible with current setup</>',
                    ];
                }
            }

            $progressBar->advance();
        };

        array_map($iterator, $directories);

        $progressBar->setMessage('Finished checking all vendor dependencies');
        $progressBar->display();
        $progressBar->finish();
        $io->newLine();

        return $rows;
    }

    /**
     * Check dependencies in the root project or a tool dependency group.
     *
     * @throws JsonException
     *
     * @return list<OutdatedPackage>
     */
    private function processNamespacePath(string $path, string $level, float $timeout): array
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

        $process = new Process($command, $path, timeout: $timeout);
        $process->enableOutput();

        $this->runProcess($process);

        return $this->decodeComposerOutput($process->getOutput(), $path);
    }

    private function runProcess(Process $process): void
    {
        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException(sprintf(
                "Dependency check in '%s' timed out after %s seconds. Increase --timeout to allow more time.",
                $process->getWorkingDirectory() ?? $this->projectDir,
                $exception->getExceededTimeout() ?? 60.0,
            ), previous: $exception);
        }

        $this->ensureProcessSucceeded($process);
    }

    /**
     * @throws JsonException
     *
     * @return list<OutdatedPackage>
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

        $installed = [];

        if ($decoded !== []) {
            $installed = $this->validateComposerResponse($decoded, $path);
        }

        return $installed;
    }

    /**
     * @return list<OutdatedPackage>
     */
    private function validateComposerResponse(mixed $decoded, string $path): array
    {
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

        $installed = [];

        foreach ($decoded->installed as $index => $package) {
            if (!$package instanceof stdClass) {
                throw new RuntimeException(sprintf(
                    "Invalid Composer response in '%s': package at index %d must be an object.",
                    $path,
                    $index,
                ));
            }

            $installed[] = $this->validateComposerPackage($package, $index, $path);
        }

        return $installed;
    }

    private function validateComposerPackage(stdClass $package, int $index, string $path): OutdatedPackage
    {
        return new OutdatedPackage(
            name: $this->getRequiredPackageField($package, 'name', $index, $path),
            version: $this->getRequiredPackageField($package, 'version', $index, $path),
            description: $this->getOptionalPackageField($package, 'description', $index, $path),
            latest: $this->getOptionalPackageField($package, 'latest', $index, $path),
            warning: $this->getOptionalPackageField($package, 'warning', $index, $path),
        );
    }

    private function getRequiredPackageField(stdClass $package, string $field, int $index, string $path): string
    {
        $value = $package->{$field} ?? null;

        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf(
                "Invalid Composer response in '%s': package at index %d requires a non-empty string '%s'.",
                $path,
                $index,
                $field,
            ));
        }

        return $value;
    }

    private function getOptionalPackageField(stdClass $package, string $field, int $index, string $path): ?string
    {
        $value = $package->{$field} ?? null;

        if (($value !== null && !is_string($value))
            || ($value === null && $field !== 'description' && property_exists($package, $field))
        ) {
            throw new RuntimeException(sprintf(
                "Invalid Composer response in '%s': package at index %d has an invalid '%s' field.",
                $path,
                $index,
                $field,
            ));
        }

        return $value;
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
    private function getPackageRow(string $relativePath, OutdatedPackage $data): array
    {
        return [
            $relativePath,
            $data->name,
            $data->description ?? '',
            $data->version,
            '<fg=yellow>' . ($data->latest ?? $data->version) . '</>',
        ];
    }

    private function getGroupPath(string $directory): string
    {
        $relativePath = Path::makeRelative($directory, $this->projectDir);

        return $relativePath === '' ? '.' : $relativePath;
    }

    private function setTableColumnWidths(int $packageNameLength, Table $table): void
    {
        $headers = $this->getHeaders();
        $packageNameLength = max(strlen($headers[1]), min($packageNameLength, 95 - strlen($headers[2])));

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
