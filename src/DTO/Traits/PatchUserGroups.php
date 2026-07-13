<?php
declare(strict_types = 1);

/**
 * /src/DTO/Traits/PatchUserGroups.php
 */

namespace App\DTO\Traits;

use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\UserGroup as UserGroupEntity;
use function array_map;

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
