<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/LogEntityTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait LogEntityTrait
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property \App\Entity\User|null $user
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
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.agent",
     *      "LogRequest",
     *      "LogRequest.agent",
     *  })
     *
     * @ORM\Column(
     *      name="agent",
     *      type="text",
     *      nullable=false,
     *  )
     */
    protected $agent;

    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.httpHost",
     *      "LogRequest",
     *      "LogRequest.httpHost",
     *  })
     *
     * @ORM\Column(
     *      name="http_host",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    protected $httpHost;

    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.clientIp",
     *      "LogRequest",
     *      "LogRequest.clientIp",
     *  })
     *
     * @ORM\Column(
     *      name="client_ip",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $clientIp;

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
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getAgent(): string
    {
        return $this->agent;
    }

    /**
     * @return string
     */
    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    /**
     * @return string
     */
    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    /**
     * @ORM\PrePersist()
     */
    protected function processTimeAndDate(): void
    {
        $now = new \DateTime('NOW', new \DateTimeZone('UTC'));

        $this->time = $this->time ?? $now;
        $this->date = $this->time ?? $now;
    }

    /**
     * @param Request $request
     */
    protected function processRequestData(Request $request): void
    {
        $this->clientIp = (string) $request->getClientIp();
        $this->httpHost = $request->getHttpHost();
        $this->agent = (string) $request->headers->get('User-Agent');
    }
}
