<?php
declare(strict_types=1);
/**
 * /src/Entity/Role.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role as BaseRole;
use Symfony\Component\Serializer\Annotation\Groups;

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
class Role extends BaseRole implements EntityInterface
{
    // Traits
    use Blameable;
    use Timestampable;

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
     *      nullable=false
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * Author books.
     *
     * @var Collection<App\Entity\UserGroup>
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
     * @param string $role The role name
     */
    public function __construct(string $role = '')
    {
        parent::__construct($role);

        $this->id = $role;
        $this->userGroups = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Collection<UserGroup>|ArrayCollection<UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }
}
