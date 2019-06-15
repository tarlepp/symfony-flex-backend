<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/User/UserPatch.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\User;

use App\Entity\User as Entity;
use App\Entity\UserGroup as UserGroupEntity;
use function array_map;

/**
 * Class UserPatch
 *
 * @package App\DTO\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserPatch extends User
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Method to update User entity user groups.
     *
     * @param Entity        $entity
     * @param UserGroupEntity[] $value
     *
     * @return User
     */
    protected function updateUserGroups(Entity $entity, array $value): User
    {
        array_map([$entity, 'addUserGroup'], $value);

        return $this;
    }
}
