<?php
declare(strict_types=1);
/**
 * /src/Entity/UserGroup.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserGroup
 *
 * @ORM\Table(
 *      name="user_group",
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\UserGroupRepository",
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroup implements EntityInterface
{
    // Traits
    use Blameable;
    use Timestampable;

    /**
     * @var string
     *
     * @Groups({
     *      "UserGroup",
     *      "UserGroup.id",
     *      "User.userGroups",
     *      "Role.userGroups",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="guid",
     *      nullable=false
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * @var \App\Entity\Role
     *
     * @Groups({
     *      "UserGroup.role",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     *
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\Role",
     *      inversedBy="userGroups",
     *  )
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(
     *          name="role",
     *          referencedColumnName="role",
     *          onDelete="CASCADE",
     *      ),
     *  })
     */
    private $role;

    /**
     * @var string
     *
     * @Groups({
     *      "UserGroup",
     *      "UserGroup.name",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     *
     * @ORM\Column(
     *      name="name",
     *      type="string",
     *      length=255,
     *      nullable=false
     *  )
     */
    private $name = '';

    /**
     * @var Collection<App\Entity\User>
     *
     * @Groups({
     *      "UserGroup.users",
     *  })
     *
     * @ORM\ManyToMany(
     *      targetEntity="User",
     *      mappedBy="userGroups",
     *  )
     * @ORM\JoinTable(
     *      name="user_has_user_group"
     *  )
     */
    private $users;

    /**
     * UserGroup constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();

        $this->users = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return $this->role;
    }

    /**
     * @param Role $role
     *
     * @return UserGroup
     */
    public function setRole(Role $role): UserGroup
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return UserGroup
     */
    public function setName(string $name): UserGroup
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<User>|ArrayCollection<User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * Method to attach new user group to user.
     *
     * @param   User    $user
     *
     * @return  UserGroup
     */
    public function addUser(User $user): UserGroup
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addUserGroup($this);
        }

        return $this;
    }

    /**
     * Method to remove specified user from user group.
     *
     * @param   User    $user
     *
     * @return  UserGroup
     */
    public function removeUser(User $user): UserGroup
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeUserGroup($this);
        }

        return $this;
    }

    /**
     * Method to remove all many-to-many user relations from current user group.
     *
     * @return  UserGroup
     */
    public function clearUsers(): UserGroup
    {
        $this->users->clear();

        return $this;
    }
}
