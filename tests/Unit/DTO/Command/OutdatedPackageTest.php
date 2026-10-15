<?php
declare(strict_types = 1);

/**
 * /tests/Unit/DTO/Command/OutdatedPackageTest.php
 */

namespace App\Tests\Unit\DTO\Command;

use App\DTO\Command\OutdatedPackage;
use PHPUnit\Framework\TestCase;

final class OutdatedPackageTest extends TestCase
{
    public function testConstructorPreservesProvidedValues(): void
    {
        $package = new OutdatedPackage(
            name: 'vendor/package',
            version: '1.0.0',
            description: 'Example package',
            latest: '1.1.0',
            warning: 'Example warning',
        );

        self::assertSame('vendor/package', $package->name);
        self::assertSame('1.0.0', $package->version);
        self::assertSame('Example package', $package->description);
        self::assertSame('1.1.0', $package->latest);
        self::assertSame('Example warning', $package->warning);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $package = new OutdatedPackage(name: 'vendor/package', version: '1.0.0');

        self::assertNull($package->description);
        self::assertNull($package->latest);
        self::assertNull($package->warning);
    }
}
