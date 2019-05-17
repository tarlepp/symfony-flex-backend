<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Blameable.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait Blameable
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait Blameable
{
    /**
     * @var User|null
     *
     * @Gedmo\Blameable(on="create")
     *
     * @Groups({
     *     "Role.createdBy",
     *     "User.createdBy",
     *     "UserGroup.createdBy",
     *  })
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(
     *     name="created_by_id",
     *     referencedColumnName="id",
     *     nullable=true,
     *     onDelete="SET NULL",
     *  )
     */
    protected $createdBy;

    /**
     * @var User|null
     *
     * @Gedmo\Blameable(on="update")
     *
     * @Groups({
     *     "Role.updatedBy",
     *     "User.updatedBy",
     *     "UserGroup.updatedBy",
     *  })
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(
     *      name="updated_by_id",
     *      referencedColumnName="id",
     *      nullable=true,
     *      onDelete="SET NULL",
     *  )
     */
    protected $updatedBy;

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     *
     * @return $this
     */
    public function setCreatedBy(?User $createdBy = null): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    /**
     * @param User|null $updatedBy
     *
     * @return $this
     */
    public function setUpdatedBy(?User $updatedBy = null): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
