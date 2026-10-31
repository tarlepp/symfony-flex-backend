<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Command/Utils/CheckDependenciesTest.php
 */

namespace App\Tests\Unit\Command\Utils;

use App\Command\Utils\CheckDependencies;
use App\DTO\Command\OutdatedPackage;
use App\Tests\Utils\PhpUnitUtil;
use JsonException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function getenv;
use function json_encode;
use function putenv;
use function str_repeat;
use function strlen;
use function sys_get_temp_dir;
use const PHP_BINARY;

final class CheckDependenciesTest extends TestCase
{
    #[TestWith([0, 10, 85])]
    #[TestWith([30, 30, 65])]
    #[TestWith([84, 84, 11])]
    #[TestWith([95, 84, 11])]
    #[TestWith([200, 84, 11])]
    public function testTableColumnWidthsAreBounded(int $length, int $packageWidth, int $descriptionWidth): void
    {
        $output = new BufferedOutput();
        $table = new Table($output);
        $command = new CheckDependencies('/project');

        PhpUnitUtil::callMethod($command, 'setTableColumnWidths', [$length, $table]);

        $expected = [23, $packageWidth, $descriptionWidth, 10, 11];
        self::assertSame($expected, PhpUnitUtil::getProperty('configuredColumnWidths', $table));
        self::assertSame($expected, PhpUnitUtil::getProperty('columnMaxWidths', $table));

        $table->setHeaders(['Path', 'Dependency', 'Description', 'Version', 'New version']);
        $table->setRows([['.', 'vendor/' . str_repeat('package', 30), 'Example description', '1.0.0', '1.1.0']]);
        $table->render();

        self::assertGreaterThan(0, strlen($output->fetch()));
    }

    #[TestWith(['/project', '.'])]
    #[TestWith(['/project/tools/02_phpstan', 'tools/02_phpstan'])]
    public function testGroupPathsAreProjectRelative(string $directory, string $expected): void
    {
        self::assertSame(
            $expected,
            PhpUnitUtil::callMethod(new CheckDependencies('/project'), 'getGroupPath', [$directory]),
        );
    }

    #[TestWith(['.'])]
    #[TestWith(['tools/02_phpstan'])]
    #[TestWith([''])]
    public function testPackageRowsPreserveGroupLabels(string $groupPath): void
    {
        $package = new OutdatedPackage(
            name: 'vendor/package',
            version: '1.0.0',
            description: 'Example package',
            latest: '1.1.0',
        );

        self::assertSame(
            [$groupPath, 'vendor/package', 'Example package', '1.0.0', '<fg=yellow>1.1.0</>'],
            PhpUnitUtil::callMethod(new CheckDependencies('/project'), 'getPackageRow', [$groupPath, $package]),
        );
    }

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

    public function testTimeoutDefaultsToSixtySeconds(): void
    {
        $option = new CheckDependencies(__DIR__)->getDefinition()->getOption('timeout');

        self::assertSame('60', $option->getDefault());
        self::assertTrue($option->isValueRequired());
    }

    #[TestWith(['invalid'])]
    #[TestWith([''])]
    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['INF'])]
    #[TestWith(['NaN'])]
    #[TestWith(['1e999'])]
    public function testInvalidTimeoutsAreRejected(string $timeout): void
    {
        $tester = new CommandTester(new CheckDependencies(__DIR__));
        $status = $tester->execute([
            '--timeout' => $timeout,
        ]);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString(
            'The --timeout option must be positive, finite seconds.',
            $tester->getDisplay(),
        );
        self::assertStringNotContainsString('Starting to check dependencies', $tester->getDisplay());
    }

    public function testProcessTimeoutIdentifiesTheGroup(): void
    {
        $process = new Process([PHP_BINARY, '-r', 'sleep(10);'], __DIR__, timeout: 0.01);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            "Dependency check in '" . __DIR__
            . "' timed out after 0.01 seconds. Increase --timeout to allow more time.",
        );

        PhpUnitUtil::callMethod(new CheckDependencies(__DIR__), 'runProcess', [$process]);
    }

    public function testProcessWithinTimeoutSucceeds(): void
    {
        $process = new Process([PHP_BINARY, '-r', 'echo "completed";'], __DIR__, timeout: 60);

        PhpUnitUtil::callMethod(new CheckDependencies(__DIR__), 'runProcess', [$process]);

        self::assertSame('completed', $process->getOutput());
        self::assertSame(0, $process->getExitCode());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testCommandDoesNotResetTheTerminal(bool $decorated): void
    {
        $tester = new CommandTester(new CheckDependencies(__DIR__));
        $tester->execute([
            '--level' => 'invalid',
        ], [
            'decorated' => $decorated,
        ]);

        self::assertStringNotContainsString("\033\143", $tester->getDisplay());
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

    #[TestWith(['{"installed":[]}', 'no vendor dependency updates were found'])]
    #[TestWith([
        '{"installed":[{"name":"vendor/package","version":"1.0.0","latest":"1.1.0"}]}',
        'Vendor dependency updates are available',
    ])]
    public function testUpdateFindingsRemainInformational(string $response, string $message): void
    {
        $filesystem = new Filesystem();
        $projectDir = $filesystem->tempnam(sys_get_temp_dir(), 'dependency-outcomes-');
        $filesystem->remove($projectDir);
        $originalPath = getenv('PATH');

        try {
            $filesystem->mkdir([$projectDir . '/tools', $projectDir . '/bin']);
            $filesystem->dumpFile(
                $projectDir . '/bin/composer',
                "#!/bin/sh\nprintf '%s' '" . $response . "'\n",
            );
            $filesystem->chmod($projectDir . '/bin/composer', 0755);
            putenv('PATH=' . $projectDir . '/bin:' . ($originalPath === false ? '' : $originalPath));

            $tester = new CommandTester(new CheckDependencies($projectDir));
            self::assertSame(Command::SUCCESS, $tester->execute([]));
            self::assertStringContainsString($message, $tester->getDisplay());

            if ($response !== '{"installed":[]}') {
                self::assertStringContainsString('[WARNING]', $tester->getDisplay());
                self::assertStringNotContainsString('no vendor dependency updates', $tester->getDisplay());
            }
        } finally {
            putenv($originalPath === false ? 'PATH' : 'PATH=' . $originalPath);
            $filesystem->remove($projectDir);
        }
    }

    #[TestWith(['', '{"installed":[]}'])]
    #[TestWith(['Composer failed', ''])]
    #[TestWith(['Composer failed', 'Partial output'])]
    #[TestWith(['', ''])]
    public function testFailedProcessesAreReported(string $errorOutput, string $output): void
    {
        $process = self::createStub(Process::class);
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
        $process->expects($this->never())->method('getErrorOutput');

        self::assertNull(
            PhpUnitUtil::callMethod(new CheckDependencies(__DIR__), 'ensureProcessSucceeded', [$process]),
        );
    }

    #[TestWith(['[]'])]
    #[TestWith(['{"installed":[]}'])]
    public function testEmptyComposerResultsAreAccepted(string $output): void
    {
        self::assertSame(
            [],
            PhpUnitUtil::callMethod(new CheckDependencies(__DIR__), 'decodeComposerOutput', [$output, __DIR__]),
        );
    }

    #[TestWith([
        '{"name":"vendor/package","version":"1.0.0","description":"Example","latest":"1.1.0","warning":"Warning"}',
        'Example',
        '1.1.0',
        'Warning',
    ])]
    #[TestWith(['{"name":"vendor/package","version":"1.0.0"}'])]
    #[TestWith(['{"name":"vendor/package","version":"1.0.0","description":null}'])]
    #[TestWith([
        '{"name":"vendor/package","version":"1.0.0","description":"","latest":"","warning":""}',
        '',
        '',
        '',
    ])]
    public function testValidComposerPackagesArePreserved(
        string $package,
        ?string $description = null,
        ?string $latest = null,
        ?string $warning = null,
    ): void {
        self::assertEquals(
            [new OutdatedPackage('vendor/package', '1.0.0', $description, $latest, $warning)],
            PhpUnitUtil::callMethod(
                new CheckDependencies(__DIR__),
                'decodeComposerOutput',
                ['{"installed":[' . $package . ']}', __DIR__],
            ),
        );
    }

    #[TestWith(['null'])]
    #[TestWith(['true'])]
    #[TestWith(['42'])]
    #[TestWith(['"unexpected"'])]
    #[TestWith(['[{}]'])]
    #[TestWith(['{}'])]
    #[TestWith(['{"installed":null}'])]
    #[TestWith(['{"installed":"unexpected"}'])]
    #[TestWith(['{"installed":{}}'])]
    #[TestWith(['{"installed":[null]}'])]
    #[TestWith(['{"installed":[{}]}'])]
    #[TestWith(['{"installed":[{"name":"","version":"1.0.0"}]}'])]
    #[TestWith(['{"installed":[{"name":42,"version":"1.0.0"}]}'])]
    #[TestWith(['{"installed":[{"name":"vendor/package","version":null}]}'])]
    #[TestWith(['{"installed":[{"name":"vendor/package","version":"1.0.0","description":[]}]}'])]
    #[TestWith(['{"installed":[{"name":"vendor/package","version":"1.0.0","latest":null}]}'])]
    #[TestWith(['{"installed":[{"name":"vendor/package","version":"1.0.0","warning":false}]}'])]
    public function testInvalidComposerResponsesAreRejected(string $output): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains("Invalid Composer response in '/project/tools/example':");

        PhpUnitUtil::callMethod(
            new CheckDependencies(__DIR__),
            'decodeComposerOutput',
            [$output, '/project/tools/example'],
        );
    }

    #[TestWith(['name', null, true])]
    #[TestWith(['name', null, false])]
    #[TestWith(['name', ''])]
    #[TestWith(['name', 42])]
    #[TestWith(['version', null, true])]
    #[TestWith(['version', null, false])]
    #[TestWith(['version', ''])]
    #[TestWith(['version', []])]
    public function testRequiredPackageFieldErrorsIdentifyTheField(
        string $field,
        mixed $value,
        bool $missing = false,
    ): void {
        $package = [
            'name' => 'vendor/package',
            'version' => '1.0.0',
        ];

        if ($missing) {
            unset($package[$field]);
        } else {
            $package[$field] = $value;
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            "Invalid Composer response in '/project/tools/example': package at index 0"
            . " requires a non-empty string '" . $field . "'.",
        );

        PhpUnitUtil::callMethod(
            new CheckDependencies(__DIR__),
            'decodeComposerOutput',
            [json_encode([
                'installed' => [$package],
            ], JSON_THROW_ON_ERROR), '/project/tools/example'],
        );
    }

    #[TestWith(['description', []])]
    #[TestWith(['description', false])]
    #[TestWith(['latest', null])]
    #[TestWith(['latest', 42])]
    #[TestWith(['warning', null])]
    #[TestWith(['warning', []])]
    public function testOptionalPackageFieldErrorsIdentifyTheField(string $field, mixed $value): void
    {
        $package = [
            'name' => 'vendor/package',
            'version' => '1.0.0',
            $field => $value,
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            "Invalid Composer response in '/project/tools/example': package at index 0"
            . " has an invalid '" . $field . "' field.",
        );

        PhpUnitUtil::callMethod(
            new CheckDependencies(__DIR__),
            'decodeComposerOutput',
            [json_encode([
                'installed' => [$package],
            ], JSON_THROW_ON_ERROR), '/project/tools/example'],
        );
    }

    public function testMalformedComposerJsonIdentifiesTheGroup(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessageIsOrContains("Invalid Composer JSON in '/project/tools/example':");

        PhpUnitUtil::callMethod(
            new CheckDependencies(__DIR__),
            'decodeComposerOutput',
            ['{"installed":', '/project/tools/example'],
        );
    }
}
