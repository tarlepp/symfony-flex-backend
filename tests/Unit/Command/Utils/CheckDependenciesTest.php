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
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
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
}
