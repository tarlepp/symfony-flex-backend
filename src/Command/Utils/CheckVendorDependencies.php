<?php
declare(strict_types = 1);
/**
 * /src/Command/Utils/CheckVendorDependencies.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\Utils;

use App\Command\Traits\SymfonyStyleTrait;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Traversable;
use function array_map;
use function array_unshift;
use function basename;
use function count;
use function implode;
use function iterator_to_array;
use function sort;
use function sprintf;
use function str_replace;
use function wordwrap;

/**
 * Class CheckVendorDependencies
 *
 * @package App\Command\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CheckVendorDependencies extends Command
{
    // Traits
    use SymfonyStyleTrait;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    private $io;

    /**
     * CheckVendorDependencies constructor.
     *
     * @param string $projectDir
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(string $projectDir)
    {
        parent::__construct('check-vendor-dependencies');

        $this->setDescription('Console command to check vendor dependencies for bin');

        $this->projectDir = $projectDir;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = $this->getSymfonyStyle($input, $output);

        $directories = $this->getNamespaceDirectories();

        array_unshift($directories, $this->projectDir);

        $rows = $this->determineTableRows($directories);

        $headers = [
            'Namespace',
            'Path',
            'Dependency',
            'Description',
            'Version',
            'New version',
        ];

        !empty($rows)
            ? $this->io->table($headers, $rows)
            : $this->io->success('Good news, there is not any vendor dependency to update at this time!');

        return null;
    }

    /**
     * Method to determine all namespace directories under 'vendor-bin' directory.
     *
     * @return string[]
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    private function getNamespaceDirectories(): array
    {
        // Find all main namespace directories under 'vendor-bin' directory
        $finder = (new Finder())
            ->depth(1)
            ->ignoreDotFiles(true)
            ->directories()
            ->in($this->projectDir . DIRECTORY_SEPARATOR . 'vendor-bin/');

        /**
         * Closure to return pure path from current SplFileInfo object.
         *
         * @param SplFileInfo $fileInfo
         *
         * @return string
         */
        $closure = function (SplFileInfo $fileInfo): string {
            return $fileInfo->getPath();
        };

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
     * @param string[] $directories
     *
     * @return string[]
     *
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
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
                $title = '';
                $relativePath = '';

                // First row of current library
                if ($row === 0) {
                    // We want to add table separator between different libraries
                    if (count($rows) > 0) {
                        $rows[] = new TableSeparator();
                    }

                    $title = basename($directory);
                    $relativePath = str_replace($this->projectDir, '', $directory) . '/composer.json';
                }

                $rows[] = [
                    $title,
                    $relativePath,
                    $data->name,
                    wordwrap((string)$data->description, 60),
                    $data->version,
                    $data->latest,
                ];
            }

            $progressBar->advance();
        };

        array_map($iterator, $directories);

        return $rows;
    }

    /**
     * Method to process namespace inside 'vendor-bin' directory.
     *
     * @param string $path
     *
     * @return \stdClass[]
     *
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
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

        if (!empty($process->getErrorOutput())
            && !($process->getExitCode() === 0 || $process->getExitCode() === null)
        ) {
            $message = sprintf(
                "Running command '%s' failed with error message:\n%s",
                implode(' ', $command),
                $process->getErrorOutput()
            );

            throw new RuntimeException($message);
        }

        return json_decode($process->getOutput())->installed ?? [];
    }

    /**
     * Helper method to get progress bar for console.
     *
     * @param int    $steps
     * @param string $message
     *
     * @return ProgressBar
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
