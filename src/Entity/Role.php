<?php
declare(strict_types=1);
/**
 * /src/Entity/Role.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

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
 * @ORM\Entity(
 *      repositoryClass="App\Repository\RoleRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Role extends BaseRole
{
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
    private $role = '';

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
     *      cascade={"all"},
     *  )
     */
    private $userGroups;

    /**
     * Constructor.
     *
     * @param string $role The role name
     */
    public function __construct($role = '')
    {
        parent::__construct($role);

        $this->userGroups = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->role;
    }

    /**
     * @return Collection<UserGroup>|ArrayCollection<UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }
}
