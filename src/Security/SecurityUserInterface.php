<?php
declare(strict_types = 1);
/**
 * /src/Security/SecurityUserInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface SecurityUserInterface
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface SecurityUserInterface extends UserInterface
{
    /**
     * @return string
     */
    public function getUuid(): string;
}
