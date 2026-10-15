<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Command/Utils/CheckDependenciesTest.php
 */

namespace App\Tests\Unit\Command\Utils;

use App\Command\Utils\CheckDependencies;
use App\Tests\Utils\PhpUnitUtil;
use JsonException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function json_decode;
use function sys_get_temp_dir;

final class CheckDependenciesTest extends TestCase
{
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
        $package = (object)[
            'name' => 'vendor/package',
            'description' => 'Example package',
            'version' => '1.0.0',
            'latest' => '1.1.0',
        ];

        self::assertSame(
            [$groupPath, 'vendor/package', 'Example package', '1.0.0', '1.1.0'],
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
    ])]
    #[TestWith(['{"name":"vendor/package","version":"1.0.0"}'])]
    #[TestWith(['{"name":"vendor/package","version":"1.0.0","description":null}'])]
    public function testValidComposerPackagesArePreserved(string $package): void
    {
        self::assertEquals(
            [json_decode($package, flags: JSON_THROW_ON_ERROR)],
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
