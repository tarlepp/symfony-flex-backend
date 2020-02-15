<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogLoginFailure.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Uuid;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class LogLoginFailure
 *
 * @ORM\Table(
 *      name="log_login_failure",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *      }
 *  )
 * @ORM\Entity(
 *      readOnly=true
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailure implements EntityInterface
{
    // Traits
    use Uuid;

    /**
     * @var UuidInterface
     *
     * @Groups({
     *      "LogLoginFailure",
     *      "LogLoginFailure.id",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     *
     * @SWG\Property(type="string", format="uuid")
     */
    private UuidInterface $id;

    /**
     * @var User
     *
     * @Groups({
     *      "LogLoginFailure",
     *      "LogLoginFailure.user",
     *  })
     *
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
    private User $user;

    /**
     * @var DateTimeImmutable
     *
     * @Groups({
     *      "LogLoginFailure",
     *      "LogLoginFailure.timestamp",
     *  })
     *
     * @ORM\Column(
     *      name="timestamp",
     *      type="datetime_immutable",
     *      nullable=false,
     *  )
     */
    private DateTimeImmutable $timestamp;

    /**
     * LogLoginFailure constructor.
     *
     * @param User $user
     *
     * @throws Throwable
     */
    public function __construct(User $user)
    {
        $this->id = $this->createUuid();
        $this->user = $user;
        $this->timestamp = new DateTimeImmutable('NOW', new DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id->toString();
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getTimestamp(): DateTimeImmutable
    {
        return $this->getCreatedAt();
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
