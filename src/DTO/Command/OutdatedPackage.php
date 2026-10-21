<?php
declare(strict_types = 1);

/**
 * /src/DTO/Command/OutdatedPackage.php
 */

namespace App\DTO\Command;

final readonly class OutdatedPackage
{
    public function __construct(
        public string $name,
        public string $version,
        public ?string $description = null,
        public ?string $latest = null,
        public ?string $warning = null,
    ) {
    }
}
