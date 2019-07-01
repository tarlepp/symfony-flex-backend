<?php
declare(strict_types = 1);
/**
 * /src/DTO/Traits/PatchUserGroups.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\Traits;

use App\Entity\UserGroup as UserGroupEntity;
use App\Entity\UserGroupAwareInterface;

/**
 * Trait PatchUserGroups
 *
 * @package App\DTO\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait PatchUserGroups
{
    /**
     * Method to patch entity user groups.
     *
     * @param UserGroupAwareInterface $entity
     * @param UserGroupEntity[]       $value
     */
    protected function updateUserGroups(UserGroupAwareInterface $entity, array $value): void
    {
        array_map([$entity, 'addUserGroup'], $value);
    }
}
