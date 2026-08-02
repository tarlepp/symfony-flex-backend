<?php
declare(strict_types = 1);

/**
 * /src/Entity/Traits/Blameable.php
 */

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;

trait Blameable
{
    #[ORM\ManyToOne(
        targetEntity: User::class,
    )]
    #[ORM\JoinColumn(
        name: 'created_by_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    #[Gedmo\Blameable(
        on: 'create',
    )]
    #[Groups([
        'ApiKey.createdBy',
        'Role.createdBy',
        'User.createdBy',
        'UserGroup.createdBy',
    ])]
    protected ?User $createdBy = null;

    #[ORM\ManyToOne(
        targetEntity: User::class,
    )]
    #[ORM\JoinColumn(
        name: 'updated_by_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    #[Gedmo\Blameable(
        on: 'update',
    )]
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
