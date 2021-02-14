<?php
declare(strict_types = 1);
/**
 * /src/DTO/Traits/PatchUserGroups.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\Traits;

use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\UserGroup as UserGroupEntity;
use function array_map;

/**
 * Trait PatchUserGroups
 *
 * @package App\DTO\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait PatchUserGroups
{
    /**
     * Method to patch entity user groups.
     *
     * @param array<int, UserGroupEntity> $value
     */
    protected function updateUserGroups(UserGroupAwareInterface $entity, array $value): self
    {
        array_map(
            static fn (UserGroupEntity $userGroup): UserGroupAwareInterface => $entity->addUserGroup($userGroup),
            $value,
        );

        return $this;
    }
}
