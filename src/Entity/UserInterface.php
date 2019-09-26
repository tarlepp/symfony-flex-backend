<?php
declare(strict_types = 1);
/**
 * /src/Entity/UserInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use Ramsey\Uuid\UuidInterface;

/**
 * Interface UserInterface
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface UserInterface
{
    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface;

    /**
     * @return string
     */
    public function getUsername(): string;

    /**
     * @return string
     */
    public function getEmail(): string;
}
