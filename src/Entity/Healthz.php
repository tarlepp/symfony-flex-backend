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
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Healthz
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity]
#[ORM\Table(name: 'healthz')]
class Healthz implements EntityInterface
{
    use Uuid;

    /**
     * @OA\Property(type="string", format="uuid")
     */
    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: 'uuid_binary_ordered_time',
        unique: true,
    )]
    #[Groups([
        'Healthz',
        'Healthz.id',
    ])]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'timestamp',
        type: 'datetime_immutable',
    )]
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
