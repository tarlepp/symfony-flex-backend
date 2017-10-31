<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/LogEntityTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait LogEntityTrait
 *
 * @package App\Entity\Traits
 */
trait LogEntityTrait
{
    /**
     * @var \DateTime
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.time",
     *      "LogRequest",
     *      "LogRequest.time",
     *  })
     *
     * @ORM\Column(
     *      name="time",
     *      type="datetime",
     *      nullable=false,
     *  )
     */
    protected $time;

    /**
     * @var \DateTime
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.date",
     *      "LogRequest",
     *      "LogRequest.date",
     *  })
     *
     * @ORM\Column(
     *      name="`date`",
     *      type="date",
     *      nullable=false,
     *  )
     */
    protected $date;

    /**
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @ORM\PrePersist()
     */
    public function processTimeAndDate(): void
    {
        $date = new \DateTime('NOW', new \DateTimeZone('UTC'));

        $this->time = $this->time ?? $date;
        $this->date = $this->time ?? $date;
    }
}
