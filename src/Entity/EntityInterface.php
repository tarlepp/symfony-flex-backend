<?php
declare(strict_types = 1);
/**
 * /src/Entity/EntityInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface EntityInterface
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface EntityInterface
{
    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface;

    /**
     * Returns createdAt.
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable;
}
