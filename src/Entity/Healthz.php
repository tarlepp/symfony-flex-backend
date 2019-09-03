<?php
declare(strict_types = 1);
/**
 * /src/Entity/Healthz.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Healthz
 *
 * @ORM\Table(
 *      name="healthz",
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Healthz implements EntityInterface
{
    /**
     * @var string
     *
     * @Groups({
     *      "Healthz",
     *      "Healthz.id",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="guid",
     *      nullable=false
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * @var DateTimeImmutable
     *
     * @Groups({
     *      "Healthz",
     *      "Healthz.timestamp",
     *  })
     *
     * @ORM\Column(
     *      name="timestamp",
     *      type="datetime_immutable",
     *      nullable=false,
     *  )
     */
    private $timestamp;

    /**
     * Healthz constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();

        $this->setTimestamp(new DateTimeImmutable('NOW', new DateTimeZone('UTC')));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getTimestamp(): DateTimeImmutable
    {
        return $this->getCreatedAt();
    }

    /**
     * @param DateTimeImmutable $timestamp
     *
     * @return Healthz
     */
    public function setTimestamp(DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}
