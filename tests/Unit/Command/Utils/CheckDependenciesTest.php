<?php
declare(strict_types = 1);

/**
 * /tests/Unit/Command/Utils/CheckDependenciesTest.php
 */

namespace App\Tests\Unit\Command\Utils;

use App\Command\Utils\CheckDependencies;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

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
}
