<?php
declare(strict_types = 1);
/**
 * /src/Entity/Healthz.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Uuid;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class Healthz implements EntityInterface
{
    use Uuid;

    /**
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     *
     * @OA\Property(type="string", format="uuid")
     */
    #[Groups([
        'Healthz',
        'Healthz.id',
    ])]
    private UuidInterface $id;

    /**
     * @ORM\Column(
     *      name="timestamp",
     *      type="datetime_immutable",
     *      nullable=false,
     *  )
     */
    #[Groups([
        'Healthz',
        'Healthz.timestamp',
    ])]
    private DateTimeImmutable $timestamp;

    /**
     * Healthz constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
        $this->timestamp = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    #[Pure]
    public function getTimestamp(): DateTimeImmutable
    {
        return $this->getCreatedAt();
    }

    public function setTimestamp(DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}
