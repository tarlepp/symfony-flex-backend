<?php
declare(strict_types = 1);
/**
 * /src/Entity/Role.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Role
 *
 * @ORM\Table(
 *      name="role",
 *      uniqueConstraints={
 * @ORM\UniqueConstraint(name="uq_role", columns={"role"}),
 *      },
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Role implements EntityInterface
{
    use Blameable;
    use Timestampable;

    /**
     * @Groups({
     *      "Role",
     *      "Role.role",
     *
     *      "UserGroup.role",
     *
     *      "set.UserProfile",
     *      "set.UserProfileGroups",
     *      "set.UserGroupBasic",
     *  })
     *
     * @ORM\Column(
     *      name="role",
     *      type="string",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     */
    private string $id;

    /**
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
    private string $description = '';

    /**
     * User groups that belongs to this role.
     *
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
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
    private Collection $userGroups;

    /**
     * Constructor.
     *
     * @param string $role The role name
     *
     * @throws Throwable
     */
    public function __construct(string $role)
    {
        $this->id = $role;
        $this->userGroups = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }
}
