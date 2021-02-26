<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Blameable.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait Blameable
{
    /**
     * @Gedmo\Blameable(on="create")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(
     *      name="created_by_id",
     *      referencedColumnName="id",
     *      nullable=true,
     *      onDelete="SET NULL",
     *  )
     */
    #[Groups([
        'ApiKey.createdBy',
        'Role.createdBy',
        'User.createdBy',
        'UserGroup.createdBy',
    ])]
    protected ?User $createdBy = null;

    /**
     * @Gedmo\Blameable(on="update")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(
     *      name="updated_by_id",
     *      referencedColumnName="id",
     *      nullable=true,
     *      onDelete="SET NULL",
     *  )
     */
    #[Groups([
        'ApiKey.updatedBy',
        'Role.updatedBy',
        'User.updatedBy',
        'UserGroup.updatedBy',
    ])]
    protected ?User $updatedBy = null;

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy = null): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy = null): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
