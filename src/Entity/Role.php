<?php
declare(strict_types = 1);
/**
 * /src/Entity/Role.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Role
 *
 * @ORM\Table(
 *      name="role",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uq_role", columns={"role"}),
 *      },
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Role implements EntityInterface
{
    // Traits
    use Blameable;
    use Timestampable;
    use Uuid;

    /**
     * @var UuidInterface
     *
     * @Groups({
     *      "Role",
     *      "Role.id",
     *      "UserGroup.role",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * @var string
     *
     * @Groups({
     *      "Role",
     *      "Role.role",
     *      "UserGroup.role",
     *  })
     *
     * @ORM\Column(
     *      name="role",
     *      type="string",
     *      unique=true,
     *      nullable=false,
     *  )
     */
    private $role;

    /**
     * @var string
     *
     * @Groups({
     *      "Role",
     *      "Role.description",
     *  })
     *
     * @ORM\Column(
     *      name="description",
     *      type="text",
     *      nullable=false
     *  )
     */
    private $description = '';

    /**
     * User groups that belongs to this role.
     *
     * @var Collection|ArrayCollection|Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     *
     * @Groups({
     *      "Role.userGroups",
     *  })
     *
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\UserGroup",
     *      mappedBy="role",
     *  )
     */
    private $userGroups;

    /**
     * Constructor.
     *
     * @param string|null $role The role name
     *
     * @throws Throwable
     */
    public function __construct(?string $role = null)
    {
        $this->id = $this->getUuid();
        $this->setRole($role ?? '');
        $this->userGroups = new ArrayCollection();
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return Role
     */
    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Role
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|ArrayCollection|Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }
}
