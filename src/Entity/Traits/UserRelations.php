<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/UserRelations.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\LogLogin;
use App\Entity\LogLoginFailure;
use App\Entity\LogRequest;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class UserRelations
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UserRelations
{
    /**
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     *
     * @Groups({
     *      "User.userGroups",
     *  })
     *
     * @ORM\ManyToMany(
     *      targetEntity="UserGroup",
     *      inversedBy="users",
     *  )
     * @ORM\JoinTable(
     *      name="user_has_user_group"
     *  )
     */
    protected Collection $userGroups;

    /**
     * @var Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     *
     * @Groups({
     *      "User.logsRequest",
     *  })
     *
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\LogRequest",
     *      mappedBy="user",
     *  )
     */
    protected Collection $logsRequest;

    /**
     * @var Collection<int, LogLogin>|ArrayCollection<int, LogLogin>
     *
     * @Groups({
     *      "User.logsLogin",
     *  })
     *
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\LogLogin",
     *      mappedBy="user",
     *  )
     */
    protected Collection $logsLogin;

    /**
     * @var Collection<int, LogLoginFailure>|ArrayCollection<int, LogLoginFailure>
     *
     * @Groups({
     *      "User.logsLoginFailure",
     *  })
     *
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\LogLoginFailure",
     *      mappedBy="user",
     *  )
     */
    protected Collection $logsLoginFailure;

    /**
     * Getter for roles.
     *
     * Note that this will only return _direct_ roles that user has and
     * not the inherited ones!
     *
     * If you want to get user inherited roles you need to implement that
     * logic by yourself OR use eg. `/user/{uuid}/roles` API endpoint.
     *
     * @psalm-return array<int, string>
     *
     * @Groups({
     *      "User.roles",
     *  })
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->userGroups->map(fn (UserGroup $userGroup): string => $userGroup->getRole()->getId())->toArray();
    }

    /**
     * Getter for user groups collection.
     *
     * @return Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    /**
     * Getter for user request log collection.
     *
     * @return Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    public function getLogsRequest()
    {
        return $this->logsRequest;
    }

    /**
     * Getter for user login log collection.
     *
     * @return Collection<int, LogLogin>|ArrayCollection<int, LogLogin>
     */
    public function getLogsLogin()
    {
        return $this->logsLogin;
    }

    /**
     * Getter for user login failure log collection.
     *
     * @return Collection<int, LogLoginFailure>|ArrayCollection<int, LogLoginFailure>
     */
    public function getLogsLoginFailure()
    {
        return $this->logsLoginFailure;
    }

    /**
     * Method to attach new user group to user.
     *
     * @param UserGroup $userGroup
     *
     * @return User|UserGroupAwareInterface
     */
    public function addUserGroup(UserGroup $userGroup): UserGroupAwareInterface
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
            /** @noinspection PhpParamsInspection */
            $userGroup->addUser($this);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }

    /**
     * Method to remove specified user group from user.
     *
     * @param UserGroup $userGroup
     *
     * @return User|UserGroupAwareInterface
     */
    public function removeUserGroup(UserGroup $userGroup): UserGroupAwareInterface
    {
        if ($this->userGroups->removeElement($userGroup)) {
            /** @noinspection PhpParamsInspection */
            $userGroup->removeUser($this);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }

    /**
     * Method to remove all many-to-many user group relations from current user.
     *
     * @return User|UserGroupAwareInterface
     */
    public function clearUserGroups(): UserGroupAwareInterface
    {
        $this->userGroups->clear();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }
}
