<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogLoginFailure.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Uuid;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class LogLoginFailure
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity(
    readOnly: true,
)]
#[ORM\Table(
    name: 'log_login_failure',
)]
#[ORM\Index(
    columns: [
        'user_id',
    ],
    name: 'user_id',
)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LogLoginFailure implements EntityInterface
{
    use Uuid;

    /**
     * @OA\Property(type="string", format="uuid")
     */
    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
    )]
    #[Groups([
        'LogLoginFailure',
        'LogLoginFailure.id',
    ])]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'timestamp',
        type: Types::DATETIME_IMMUTABLE,
    )]
    #[Groups([
        'LogLoginFailure',
        'LogLoginFailure.timestamp',
    ])]
    private DateTimeImmutable $timestamp;

    /**
     * LogLoginFailure constructor.
     *
     * @throws Throwable
     */
    public function __construct(
        #[ORM\ManyToOne(
            targetEntity: User::class,
            inversedBy: 'logsLoginFailure',
        )]
        #[ORM\JoinColumn(
            name: 'user_id',
            nullable: false,
        )]
        #[Groups([
            'LogLoginFailure',
            'LogLoginFailure.user',
        ])]
        private User $user
    ) {
        $this->id = $this->createUuid();
        $this->timestamp = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->getCreatedAt();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}
