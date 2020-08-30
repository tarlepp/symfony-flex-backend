<?php
declare(strict_types = 1);
/**
 * /src/Entity/Interfaces/UserInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Interfaces;

/**
 * Interface UserInterface
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface UserInterface
{
    public function getId(): string;

    public function getUsername(): string;

    public function getEmail(): string;
}
