<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/UserRelations.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity\Traits;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Security\RolesService;
use App\Security\RolesServiceInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use function array_unique;

/**
 * Class UserRelations
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property $this User
 */
trait UserRelations
{
    /**
     * @var Collection<UserGroup>
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
     * @var Collection<LogRequest>
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
     * @var Collection<LogLogin>
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
     * @var Collection<LogLoginFailure>
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
     * @Groups({
     *      "User.roles",
     *  })
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        /**
         * Lambda iterator to get user group role information.
         *
         * @param UserGroup $userGroup
         *
         * @return string
         */
        $iterator = function (UserGroup $userGroup): string {
            return $userGroup->getRole()->getId();
        };

        // Determine base roles
        $output = $this->userGroups->map($iterator)->toArray();

        // And if we have roles service present we can fetch all inherited roles
        if ($this->rolesService instanceof RolesService) {
            $output = $this->rolesService->getInheritedRoles($output);
        }

        return array_unique($output);
    }

    /**
     * Getter for user groups collection.
     *
     * @return Collection<UserGroup>|ArrayCollection<UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    /**
     * Getter for user request log collection.
     *
     * @return Collection<LogRequest>|ArrayCollection<LogRequest>
     */
    public function getLogsRequest(): Collection
    {
        return $this->logsRequest;
    }

    /**
     * Getter for user login log collection.
     *
     * @return Collection<LogLogin>|ArrayCollection<LogLogin>
     */
    public function getLogsLogin(): Collection
    {
        return $this->logsLogin;
    }

    /**
     * Getter for user login failure log collection.
     *
     * @return Collection<LogLoginFailure>|ArrayCollection<LogLoginFailure>
     */
    public function getLogsLoginFailure(): Collection
    {
        return $this->logsLoginFailure;
    }

    /**
     * Method to attach new user group to user.
     *
     * @param UserGroup $userGroup
     *
     * @return User
     */
    public function addUserGroup(UserGroup $userGroup): User
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
     * @return User
     */
    public function removeUserGroup(UserGroup $userGroup): User
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
     * @return User
     */
    public function clearUserGroups(): User
    {
        $this->userGroups->clear();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }
}
