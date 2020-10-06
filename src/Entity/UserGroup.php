<?php
declare(strict_types = 1);
/**
 * /src/Entity/UserGroup.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * Class UserGroup
 *
 * @ORM\Table(
 *      name="user_group",
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroup implements EntityInterface, Stringable
{
    use Blameable;
    use Timestampable;
    use Uuid;

    /**
     * @Groups({
     *      "UserGroup",
     *      "UserGroup.id",
     *
     *      "ApiKey.userGroups",
     *      "User.userGroups",
     *      "Role.userGroups",
     *
     *      "set.UserProfile",
     *      "set.UserProfileGroups",
     *      "set.UserGroupBasic",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     *
     * @OA\Property(type="string", format="uuid")
     */
    private UuidInterface $id;

    /**
     * @Groups({
     *      "UserGroup.role",
     *
     *      "set.UserProfile",
     *      "set.UserProfileGroups",
     *      "set.UserGroupBasic",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Valid()
     *
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\Role",
     *      inversedBy="userGroups",
     *  )
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(
     *          name="role",
     *          referencedColumnName="role",
     *          onDelete="CASCADE",
     *      ),
     *  })
     */
    private Role $role;

    /**
     * @Groups({
     *      "UserGroup",
     *      "UserGroup.name",
     *
     *      "set.UserProfile",
     *      "set.UserProfileGroups",
     *      "set.UserGroupBasic",
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
    private string $name = '';

    /**
     * @var Collection<int, User>|ArrayCollection<int, User>
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
    private Collection $users;

    /**
     * @var Collection<int, ApiKey>|ArrayCollection<int, ApiKey>
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
    private Collection $apiKeys;

    /**
     * UserGroup constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();

        $this->users = new ArrayCollection();
        $this->apiKeys = new ArrayCollection();
    }

    public function __toString(): string
    {
        return self::class;
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>|ArrayCollection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, ApiKey>|ArrayCollection<int, ApiKey>
     */
    public function getApiKeys(): Collection
    {
        return $this->apiKeys;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addUserGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeUserGroup($this);
        }

        return $this;
    }

    public function clearUsers(): self
    {
        $this->users->clear();

        return $this;
    }

    public function addApiKey(ApiKey $apiKey): self
    {
        if (!$this->apiKeys->contains($apiKey)) {
            $this->apiKeys->add($apiKey);
            $apiKey->addUserGroup($this);
        }

        return $this;
    }

    public function removeApiKey(ApiKey $apiKey): self
    {
        if ($this->apiKeys->removeElement($apiKey)) {
            $apiKey->removeUserGroup($this);
        }

        return $this;
    }

    public function clearApiKeys(): self
    {
        $this->apiKeys->clear();

        return $this;
    }
}
