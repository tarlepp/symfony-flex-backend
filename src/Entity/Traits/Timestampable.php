<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Timestampable.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @package App\Entity\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait Timestampable
{
    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
    )]
    #[Gedmo\Timestampable(
        on: 'create',
    )]
    #[Groups([
        'ApiKey.createdAt',
        'Role.createdAt',
        'User.createdAt',
        'UserGroup.createdAt',
    ])]
    protected ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
    )]
    #[Gedmo\Timestampable(
        on: 'update',
    )]
    #[Groups([
        'ApiKey.updatedAt',
        'Role.updatedAt',
        'User.updatedAt',
        'UserGroup.updatedAt',
    ])]
    protected ?DateTimeImmutable $updatedAt = null;

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
