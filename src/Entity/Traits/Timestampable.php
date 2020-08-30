<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Timestampable.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait Timestampable
 *
 * @package App\Entity\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait Timestampable
{
    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @Groups({
     *      "ApiKey.createdAt",
     *      "Role.createdAt",
     *      "User.createdAt",
     *      "UserGroup.createdAt",
     *  })
     *
     * @ORM\Column(
     *      name="created_at",
     *      type="datetime_immutable",
     *      nullable=true,
     *  )
     */
    protected ?DateTimeImmutable $createdAt = null;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @Groups({
     *      "ApiKey.updatedAt",
     *      "Role.updatedAt",
     *      "User.updatedAt",
     *      "UserGroup.updatedAt",
     *  })
     *
     * @ORM\Column(
     *      name="updated_at",
     *      type="datetime_immutable",
     *      nullable=true,
     *  )
     */
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
