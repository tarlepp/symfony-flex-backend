<?php
declare(strict_types=1);
/**
 * /src/Entity/LogLogin.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class LogLogin
 *
 * @ORM\Table(
 *      name="log_login",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *      }
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\LogLoginRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLogin implements EntityInterface
{
    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.id",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="guid",
     *      nullable=false,
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.id",
     *  })
     *
     * @ORM\Column(
     *      name="type",
     *      type="enumLogLogin",
     *      nullable=false,
     *  )
     */
    private $type;

    /**
     * @var \App\Entity\User|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.user",
     *  })
     *
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\User",
     *      inversedBy="logsLogin",
     *  )
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(
     *          name="user_id",
     *          referencedColumnName="id",
     *          onDelete="SET NULL",
     *          nullable=true,
     *      ),
     *  })
     */
    private $user;

    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.ip",
     *  })
     *
     * @ORM\Column(
     *      name="ip",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $ip;

    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.host",
     *  })
     *
     * @ORM\Column(
     *      name="host",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $host;

    /**
     * @var string
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.agent",
     *  })
     *
     * @ORM\Column(
     *      name="agent",
     *      type="text",
     *      nullable=false,
     *  )
     */
    private $agent;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.clientType",
     *  })
     *
     * @ORM\Column(
     *      name="client_type",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $clientType;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.clientName",
     *  })
     *
     * @ORM\Column(
     *      name="client_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $clientName;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.clientShortName",
     *  })
     *
     * @ORM\Column(
     *      name="client_short_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $clientShortName;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.clientVersion",
     *  })
     *
     * @ORM\Column(
     *      name="client_version",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $clientVersion;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.clientEngine",
     *  })
     *
     * @ORM\Column(
     *      name="client_engine",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $clientEngine;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.osName",
     *  })
     *
     * @ORM\Column(
     *      name="os_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $osName;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.osShortName",
     *  })
     *
     * @ORM\Column(
     *      name="os_short_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $osShortName;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.osVersion",
     *  })
     *
     * @ORM\Column(
     *      name="os_version",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $osVersion;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.osPlatform",
     *  })
     *
     * @ORM\Column(
     *      name="os_platform",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $osPlatform;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.deviceName",
     *  })
     *
     * @ORM\Column(
     *      name="device_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $deviceName;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.brandName",
     *  })
     *
     * @ORM\Column(
     *      name="brand_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $brandName;

    /**
     * @var string|null
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.model",
     *  })
     *
     * @ORM\Column(
     *      name="model",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $model;

    /**
     * @var \DateTime
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.timestamp",
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
     * LogLogin constructor.
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return LogLogin
     */
    public function setType(string $type): LogLogin
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return LogLogin
     */
    public function setUser(User $user = null): LogLogin
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return LogLogin
     */
    public function setIp(string $ip): LogLogin
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return LogLogin
     */
    public function setHost(string $host): LogLogin
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getAgent(): string
    {
        return $this->agent;
    }

    /**
     * @param string $agent
     *
     * @return LogLogin
     */
    public function setAgent(string $agent): LogLogin
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getClientType(): ?string
    {
        return $this->clientType;
    }

    /**
     * @param null|string $clientType
     *
     * @return LogLogin
     */
    public function setClientType(string $clientType = null): LogLogin
    {
        $this->clientType = $clientType;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    /**
     * @param null|string $clientName
     *
     * @return LogLogin
     */
    public function setClientName(string $clientName = null): LogLogin
    {
        $this->clientName = $clientName;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientShortName(): string
    {
        return $this->clientShortName;
    }

    /**
     * @param null|string $clientShortName
     *
     * @return LogLogin
     */
    public function setClientShortName(string $clientShortName = null): LogLogin
    {
        $this->clientShortName = $clientShortName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getClientVersion(): ?string
    {
        return $this->clientVersion;
    }

    /**
     * @param null|string $clientVersion
     *
     * @return LogLogin
     */
    public function setClientVersion(string $clientVersion = null): LogLogin
    {
        $this->clientVersion = $clientVersion;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getClientEngine(): ?string
    {
        return $this->clientEngine;
    }

    /**
     * @param null|string $clientEngine
     *
     * @return LogLogin
     */
    public function setClientEngine(string $clientEngine = null): LogLogin
    {
        $this->clientEngine = $clientEngine;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getOsName(): ?string
    {
        return $this->osName;
    }

    /**
     * @param null|string $osName
     *
     * @return LogLogin
     */
    public function setOsName(string $osName = null): LogLogin
    {
        $this->osName = $osName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getOsShortName(): ?string
    {
        return $this->osShortName;
    }

    /**
     * @param null|string $osShortName
     *
     * @return LogLogin
     */
    public function setOsShortName(string $osShortName = null): LogLogin
    {
        $this->osShortName = $osShortName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    /**
     * @param null|string $osVersion
     *
     * @return LogLogin
     */
    public function setOsVersion(string $osVersion = null): LogLogin
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getOsPlatform(): ?string
    {
        return $this->osPlatform;
    }

    /**
     * @param null|string $osPlatform
     *
     * @return LogLogin
     */
    public function setOsPlatform(string $osPlatform = null): LogLogin
    {
        $this->osPlatform = $osPlatform;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    /**
     * @param null|string $deviceName
     *
     * @return LogLogin
     */
    public function setDeviceName(string $deviceName = null): LogLogin
    {
        $this->deviceName = $deviceName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    /**
     * @param null|string $brandName
     *
     * @return LogLogin
     */
    public function setBrandName(string $brandName = null): LogLogin
    {
        $this->brandName = $brandName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @param null|string $model
     *
     * @return LogLogin
     */
    public function setModel(string $model = null): LogLogin
    {
        $this->model = $model;

        return $this;
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
     * @return LogLogin
     */
    public function setTimestamp(\DateTime $timestamp): LogLogin
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
