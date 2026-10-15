<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Command/Utils/CheckDependenciesTest.php
 */

namespace App\Tests\Unit\Command\Utils;

use App\Command\Utils\CheckDependencies;
use App\Tests\Utils\PhpUnitUtil;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function sys_get_temp_dir;

final class CheckDependenciesTest extends TestCase
{
    #[TestWith(['invalid'])]
    #[TestWith([''])]
    #[TestWith(['PATCH'])]
    public function testInvalidUpdateLevelsAreRejected(string $level): void
    {
        $tester = new CommandTester(new CheckDependencies(__DIR__));

        $status = $tester->execute([
            '--level' => $level,
        ]);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString(
            'The --level option must be one of: latest, minor, patch.',
            $tester->getDisplay(),
        );
        self::assertStringNotContainsString('Starting to check dependencies', $tester->getDisplay());
    }

    public function testUpdateLevelDefaultsToLatest(): void
    {
        $definition = new CheckDependencies(__DIR__)->getDefinition();

        self::assertSame('latest', $definition->getOption('level')->getDefault());
        self::assertTrue($definition->getOption('level')->isValueRequired());
        self::assertFalse($definition->hasOption('minor'));
        self::assertFalse($definition->hasOption('patch'));
    }

    public function testToolGroupsAreDiscoveredByTheirComposerManifests(): void
    {
        $filesystem = new Filesystem();
        $projectDir = $filesystem->tempnam(sys_get_temp_dir(), 'check-dependencies-');
        $filesystem->remove($projectDir);

        try {
            $filesystem->mkdir([
                $projectDir . '/tools/02_multiple_children/vendor',
                $projectDir . '/tools/02_multiple_children/cache',
                $projectDir . '/tools/03_without_manifest/vendor',
                $projectDir . '/tools/.hidden',
            ]);
            $filesystem->dumpFile($projectDir . '/tools/02_multiple_children/composer.json', '{}');
            $filesystem->dumpFile($projectDir . '/tools/01_without_children/composer.json', '{}');
            $filesystem->dumpFile($projectDir . '/tools/02_multiple_children/vendor/composer.json', '{}');
            $filesystem->dumpFile($projectDir . '/tools/.hidden/composer.json', '{}');
            $filesystem->dumpFile($projectDir . '/tools/composer.json', '{}');

            self::assertSame(
                [
                    $projectDir . '/tools/01_without_children',
                    $projectDir . '/tools/02_multiple_children',
                ],
                PhpUnitUtil::callMethod(new CheckDependencies($projectDir), 'getNamespaceDirectories', []),
            );
        } finally {
            $filesystem->remove($projectDir);
        }
    }

    #[TestWith(['', '{"installed":[]}'])]
    #[TestWith(['Composer failed', ''])]
    #[TestWith(['Composer failed', 'Partial output'])]
    #[TestWith(['', ''])]
    public function testFailedProcessesAreReported(string $errorOutput, string $output): void
    {
        $process = $this->createStub(Process::class);
        $process->method('isSuccessful')->willReturn(false);
        $process->method('getCommandLine')->willReturn('composer outdated -D -f json');
        $process->method('getWorkingDirectory')->willReturn('/project/tools/example');
        $process->method('getExitCode')->willReturn(1);
        $process->method('getOutput')->willReturn($output);
        $process->method('getErrorOutput')->willReturn($errorOutput);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            "Running command 'composer outdated -D -f json' in '/project/tools/example' failed with exit code 1."
            . "\nStandard output:\n" . $output . "\nError output:\n" . $errorOutput,
        );

        PhpUnitUtil::callMethod(new CheckDependencies(__DIR__), 'ensureProcessSucceeded', [$process]);
    }

    public function testSuccessfulProcessesCanWriteToStandardError(): void
    {
        $process = $this->createMock(Process::class);
        $process->method('isSuccessful')->willReturn(true);
        $process->expects(self::never())->method('getErrorOutput');

        self::assertNull(
            PhpUnitUtil::callMethod(new CheckDependencies(__DIR__), 'ensureProcessSucceeded', [$process]),
        );
    }
}
