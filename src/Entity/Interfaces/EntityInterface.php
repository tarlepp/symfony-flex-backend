<?php
declare(strict_types = 1);
/**
 * /src/Entity/Interfaces/EntityInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity\Interfaces;

use DateTimeImmutable;

/**
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface EntityInterface
{
    public function getId(): string;
    public function getCreatedAt(): ?DateTimeImmutable;
}
