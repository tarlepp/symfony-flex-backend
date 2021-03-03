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
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class LogLoginFailure
 *
 * @ORM\Table(
 *      name="log_login_failure",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *      },
 *  )
 * @ORM\Entity(
 *      readOnly=true,
 *  )
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LogLoginFailure implements EntityInterface
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
        'LogLoginFailure',
        'LogLoginFailure.id',
    ])]
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\User",
     *      inversedBy="logsLoginFailure",
     *  )
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(
     *          name="user_id",
     *          referencedColumnName="id",
     *          nullable=false,
     *      ),
     *  })
     */
    #[Groups([
        'LogLoginFailure',
        'LogLoginFailure.user',
    ])]
    private User $user;

    /**
     * @ORM\Column(
     *      name="timestamp",
     *      type="datetime_immutable",
     *      nullable=false,
     *  )
     */
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
    public function __construct(User $user)
    {
        $this->id = $this->createUuid();
        $this->user = $user;
        $this->timestamp = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    #[Pure]
    public function getUser(): User
    {
        return $this->user;
    }

    #[Pure]
    public function getTimestamp(): DateTimeImmutable
    {
        return $this->getCreatedAt();
    }

    #[Pure]
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}
