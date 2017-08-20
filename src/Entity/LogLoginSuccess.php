<?php
declare(strict_types=1);
/**
 * /src/Entity/LogLoginSuccess.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class LogLoginSuccess
 *
 * @ORM\Table(
 *      name="log_login_success",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *      }
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\LogLoginSuccessRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginSuccess implements EntityInterface
{
    /**
     * @var string
     *
     * @Groups({
     *      "LoginLog",
     *      "LoginLog.id",
     *      "User.logins",
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
     * @var \App\Entity\User
     *
     * @Groups({
     *      "LoginLog",
     *      "LoginLog.user",
     *  })
     *
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\User",
     *      inversedBy="logsLoginSuccess",
     *  )
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(
     *          name="user_id",
     *          referencedColumnName="id",
     *          onDelete="SET NULL",
     *      ),
     *  })
     */
    private $user;

    /**
     * @var string
     *
     * @Groups({
     *      "LoginLog",
     *      "LoginLog.ip",
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
     *      "LoginLog",
     *      "LoginLog.host",
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
     *      "LoginLog",
     *      "LoginLog.agent",
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
     *      "LoginLog",
     *      "LoginLog.clientType",
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
     *      "LoginLog",
     *      "LoginLog.clientName",
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
     *      "LoginLog",
     *      "LoginLog.clientShortName",
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
     *      "LoginLog",
     *      "LoginLog.clientVersion",
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
     *      "LoginLog",
     *      "LoginLog.clientEngine",
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
     *      "LoginLog",
     *      "LoginLog.osName",
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
     *      "LoginLog",
     *      "LoginLog.osShortName",
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
     *      "LoginLog",
     *      "LoginLog.osVersion",
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
     *      "LoginLog",
     *      "LoginLog.osPlatform",
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
     *      "LoginLog",
     *      "LoginLog.deviceName",
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
     *      "LoginLog",
     *      "LoginLog.brandName",
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
     *      "LoginLog",
     *      "LoginLog.model",
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
     *      "LoginLog",
     *      "LoginLog.timestamp",
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
     * LogLoginSuccess constructor.
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
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return LogLoginSuccess
     */
    public function setUser(User $user): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setIp(string $ip): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setHost(string $host): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setAgent(string $agent): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setClientType(string $clientType = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setClientName(string $clientName = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setClientShortName(string $clientShortName = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setClientVersion(string $clientVersion = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setClientEngine(string $clientEngine = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setOsName(string $osName = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setOsShortName(string $osShortName = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setOsVersion(string $osVersion = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setOsPlatform(string $osPlatform = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setDeviceName(string $deviceName = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setBrandName(string $brandName = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setModel(string $model = null): LogLoginSuccess
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
     * @return LogLoginSuccess
     */
    public function setTimestamp(\DateTime $timestamp): LogLoginSuccess
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
