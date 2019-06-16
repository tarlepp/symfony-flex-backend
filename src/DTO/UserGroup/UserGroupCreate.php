<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/UserGroup/UserGroupCreate.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\UserGroup;

/**
 * Class UserGroupCreate
 *
 * @package App\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupCreate extends UserGroup
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @var \App\Entity\Role
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    protected $role;
}
