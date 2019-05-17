<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogLoginFailure.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

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
    /**
     * @var string
     *
     * @Groups({
     *      "LogLoginFailure",
     *      "LogLoginFailure.id",
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
     * @var \App\Entity\User
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
    private $user;

    /**
     * @var DateTime
     *
     * @Groups({
     *      "LogLoginFailure",
     *      "LogLoginFailure.timestamp",
     *  })
     *
     * @ORM\Column(
     *      name="timestamp",
     *      type="datetime",
     *      nullable=false,
     *  )
     */
    private $timestamp;

    /**
     * LogLoginFailure constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->timestamp = new DateTime('NOW', new DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }
}
