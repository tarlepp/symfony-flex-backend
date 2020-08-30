<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/UserRelations.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use App\Entity\LogLogin;
use App\Entity\LogLoginFailure;
use App\Entity\LogRequest;
use App\Entity\UserGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class UserRelations
 *
 * @package App\Entity\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UserRelations
{
    /**
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     *
     * @Groups({
     *      "User.userGroups",
     *
     *      "set.UserProfile",
     *  })
     *
     * @ORM\ManyToMany(
     *      targetEntity="App\Entity\UserGroup",
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
     * @Groups({
     *      "User.roles",
     *
     *      "set.UserProfile",
     *  })
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->userGroups
            ->map(static fn (UserGroup $userGroup): string => $userGroup->getRole()->getId())
            ->toArray();
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
     */
    public function addUserGroup(UserGroup $userGroup): self
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);

            /* @noinspection PhpParamsInspection */
            $userGroup->addUser($this);
        }

        return $this;
    }

    /**
     * Method to remove specified user group from user.
     */
    public function removeUserGroup(UserGroup $userGroup): self
    {
        if ($this->userGroups->removeElement($userGroup)) {
            /* @noinspection PhpParamsInspection */
            $userGroup->removeUser($this);
        }

        return $this;
    }

    /**
     * Method to remove all many-to-many user group relations from current
     * user.
     */
    public function clearUserGroups(): self
    {
        $this->userGroups->clear();

        return $this;
    }
}
