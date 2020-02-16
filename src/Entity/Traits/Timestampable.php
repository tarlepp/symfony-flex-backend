<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Timestampable.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use App\Entity\Interfaces\EntityInterface;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait Timestampable
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait Timestampable
{
    /**
     * @var DateTimeImmutable|null
     *
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
    protected ?DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable|null
     *
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
    protected ?DateTimeImmutable $updatedAt;

    /**
     * Sets createdAt.
     *
     * @param DateTimeImmutable $createdAt
     *
     * @return EntityInterface|$this
     */
    public function setCreatedAt(DateTimeImmutable $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @param DateTimeImmutable $updatedAt
     *
     * @return EntityInterface|$this
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
