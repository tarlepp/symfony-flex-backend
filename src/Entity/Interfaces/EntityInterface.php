<?php
declare(strict_types = 1);

/**
 * /src/Entity/Interfaces/EntityInterface.php
 */

namespace App\Entity\Interfaces;

use DateTimeImmutable;

interface EntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;
    public function getCreatedAt(): ?DateTimeImmutable;
}
