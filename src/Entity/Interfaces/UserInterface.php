<?php
declare(strict_types = 1);
/**
 * /src/Entity/Interfaces/UserInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity\Interfaces;

/**
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface UserInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;
    public function getUsername(): string;
    public function getEmail(): string;
}
