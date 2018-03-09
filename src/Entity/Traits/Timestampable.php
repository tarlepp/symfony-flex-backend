<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Timestampable.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity\Traits;

use App\Entity\EntityInterface;
use DateTime;
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
     * @var DateTime|null
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @Groups({
     *     "Role.createdAt",
     *     "User.createdAt",
     *     "UserGroup.createdAt",
     *  })
     *
     * @ORM\Column(
     *      name="created_at",
     *      type="datetime",
     *      nullable=true,
     *  )
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     *
     * @Gedmo\Timestampable(on="update")
     *
     * @Groups({
     *     "Role.updatedAt",
     *     "User.updatedAt",
     *     "UserGroup.updatedAt",
     *  })
     *
     * @ORM\Column(
     *      name="updated_at",
     *      type="datetime",
     *      nullable=true,
     *  )
     */
    protected $updatedAt;

    /**
     * Sets createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return EntityInterface|$this
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @param DateTime $updatedAt
     *
     * @return EntityInterface|$this
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
