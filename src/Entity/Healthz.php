<?php
declare(strict_types=1);
/**
 * /src/Entity/Healthz.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Class Healthz
 *
 * @ORM\Table(
 *      name="healthz",
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\HealthzRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Healthz implements EntityInterface
{
    /**
     * @var string
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
     * @var \DateTime
     *
     * @ORM\Column(
     *      name="timestamp",
     *      type="datetime",
     *      nullable=false,
     *  )
     */
    private $timestamp;

    /**
     * Healthz constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     *
     * @return Healthz
     */
    public function setTimestamp(\DateTime $timestamp): Healthz
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
