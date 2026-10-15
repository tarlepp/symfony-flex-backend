<?php
declare(strict_types = 1);

/**
 * /tests/Integration/DTO/Command/OutdatedPackageTest.php
 */

namespace App\Tests\Integration\DTO\Command;

use App\Command\Utils\CheckDependencies;
use App\DTO\Command\OutdatedPackage;
use App\Tests\Utils\PhpUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OutdatedPackageTest extends KernelTestCase
{
    public function testComposerResponseIsMappedToOutdatedPackages(): void
    {
        $output = '{"installed":['
            . '{"name":"vendor/first","version":"1.0.0","description":"Example","latest":"1.1.0","warning":"Warning"},'
            . '{"name":"vendor/second","version":"2.0.0"}'
            . ']}';

        $packages = PhpUnitUtil::callMethod(
            new CheckDependencies(__DIR__),
            'decodeComposerOutput',
            [$output, __DIR__],
        );

        self::assertEquals([
            new OutdatedPackage(
                name: 'vendor/first',
                version: '1.0.0',
                description: 'Example',
                latest: '1.1.0',
                warning: 'Warning',
            ),
            new OutdatedPackage(name: 'vendor/second', version: '2.0.0'),
        ], $packages);
    }
}
