<?php
declare(strict_types = 1);
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
 * @ORM\Entity()
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
     * @var Collection<App\Entity\User>|ArrayCollection<App\Entity\User>
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
     * @var Collection<App\Entity\ApiKey>|ArrayCollection<App\Entity\ApiKey>
     *
     * @Groups({
     *      "UserGroup.apiKeys",
     *  })
     *
     * @ORM\ManyToMany(
     *      targetEntity="ApiKey",
     *      mappedBy="userGroups",
     *  )
     * @ORM\JoinTable(
     *      name="api_key_has_user_group"
     *  )
     */
    private $apiKeys;

    /**
     * UserGroup constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();

        $this->users = new ArrayCollection();
        $this->apiKeys = new ArrayCollection();
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
    public function setRole(Role $role): self
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
    public function setName(string $name): self
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
     * @return Collection<ApiKey>|ArrayCollection<ApiKey>
     */
    public function getApiKeys(): Collection
    {
        return $this->apiKeys;
    }

    /**
     * Method to attach new user group to user.
     *
     * @param   User    $user
     *
     * @return  UserGroup
     */
    public function addUser(User $user): self
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
    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeUserGroup($this);
        }

        return $this;
    }

    /**
     * Method to remove all many-to-many user relations from current user group.
     *
     * @return  UserGroup
     */
    public function clearUsers(): self
    {
        $this->users->clear();

        return $this;
    }

    /**
     * Method to attach new user group to user.
     *
     * @param ApiKey $apiKey
     *
     * @return UserGroup
     */
    public function addApiKey(ApiKey $apiKey): self
    {
        if (!$this->apiKeys->contains($apiKey)) {
            $this->apiKeys->add($apiKey);
            $apiKey->addUserGroup($this);
        }

        return $this;
    }

    /**
     * Method to remove specified user from user group.
     *
     * @param ApiKey $apiKey
     *
     * @return UserGroup
     */
    public function removeApiKey(ApiKey $apiKey): self
    {
        if ($this->apiKeys->removeElement($apiKey)) {
            $apiKey->removeUserGroup($this);
        }

        return $this;
    }

    /**
     * Method to remove all many-to-many api key relations from current user group.
     *
     * @return  UserGroup
     */
    public function clearApiKeys(): self
    {
        $this->apiKeys->clear();

        return $this;
    }
}
