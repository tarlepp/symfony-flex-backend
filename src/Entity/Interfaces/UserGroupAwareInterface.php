<?php
declare(strict_types = 1);
/**
 * /src/Entity/Interfaces/UserGroupAwareInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Interfaces;

use App\Entity\UserGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Interface UserGroupAwareInterface
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface UserGroupAwareInterface extends EntityInterface
{
    /**
     * @return Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection;

    /**
     * Method to attach new userGroup to current user OR api key.
     */
    public function addUserGroup(UserGroup $userGroup): self;

    /**
     * Method to remove specified userGroup from current user OR api key.
     */
    public function removeUserGroup(UserGroup $userGroup): self;

    /**
     * Method to remove all many-to-many userGroup relations from current user
     * OR api key.
     */
    public function clearUserGroups(): self;
}
