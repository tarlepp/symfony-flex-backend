<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/UserGroup/UserGroupUpdate.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\UserGroup;

use App\Entity\Role;

/**
 * Class UserGroupUpdate
 *
 * @package App\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupUpdate extends UserGroup
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @var \App\Entity\Role
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    protected ?Role $role;
}
