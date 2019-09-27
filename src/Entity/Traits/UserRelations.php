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
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAwareInterface;
use App\Security\RolesService;
use App\Security\RolesServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use function array_unique;
use function array_values;

/**
 * Class UserRelations
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UserRelations
{
    /**
     * @var Collection|ArrayCollection|Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
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
    protected $userGroups;

    /**
     * @var Collection|ArrayCollection|Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
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
    protected $logsRequest;

    /**
     * @var Collection|ArrayCollection|Collection<int, LogLogin>|ArrayCollection<int, LogLogin>
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
    protected $logsLogin;

    /**
     * @var Collection|ArrayCollection|Collection<int, LogLoginFailure>|ArrayCollection<int, LogLoginFailure>
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
    protected $logsLoginFailure;

    /**
     * @var RolesServiceInterface
     */
    private $rolesService;

    /**
     * @param RolesServiceInterface $rolesService
     *
     * @return User
     */
    public function setRolesService(RolesServiceInterface $rolesService): User
    {
        $this->rolesService = $rolesService;

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }

    /**
     * Getter for roles.
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
        $iterator = static function (UserGroup $userGroup): string {
            return $userGroup->getRole()->getRole();
        };

        /** @var array<int, string> $output */
        $output = $this->userGroups->map($iterator)->toArray();

        // And if we have roles service present we can fetch all inherited roles
        if ($this->rolesService instanceof RolesService) {
            $output = $this->rolesService->getInheritedRoles($output);
        }

        return array_values(array_map('\strval', array_unique($output)));
    }

    /**
     * Getter for user groups collection.
     *
     * @return Collection|ArrayCollection|Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    /**
     * Getter for user request log collection.
     *
     * @return Collection|ArrayCollection|Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    public function getLogsRequest()
    {
        return $this->logsRequest;
    }

    /**
     * Getter for user login log collection.
     *
     * @return Collection|ArrayCollection|Collection<int, LogLogin>|ArrayCollection<int, LogLogin>
     */
    public function getLogsLogin()
    {
        return $this->logsLogin;
    }

    /**
     * Getter for user login failure log collection.
     *
     * @return Collection|ArrayCollection|Collection<int, LogLoginFailure>|ArrayCollection<int, LogLoginFailure>
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
