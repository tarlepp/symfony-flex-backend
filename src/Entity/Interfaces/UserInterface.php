<?php
declare(strict_types = 1);

/**
 * /src/Entity/Interfaces/UserInterface.php
 */

namespace App\Entity\Interfaces;

interface UserInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;
    public function getUsername(): string;
    public function getEmail(): string;
}
