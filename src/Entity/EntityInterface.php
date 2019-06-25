<?php
declare(strict_types = 1);
/**
 * /src/Entity/EntityInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use DateTime;

/**
 * Interface EntityInterface
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface EntityInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * Returns createdAt.
     *
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime;
}
