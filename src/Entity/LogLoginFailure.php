<?php
declare(strict_types=1);
/**
 * /src/Entity/LogLoginFailure.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

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
 *      repositoryClass="App\Repository\LogLoginFailureRepository"
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.id",
     *      "User.loginFailures",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.user",
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
     *          onDelete="SET NULL",
     *      ),
     *  })
     */
    private $user;

    /**
     * @var string
     *
     * @Groups({
     *      "LoginFailureLog",
     *      "LoginFailureLog.ip",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.host",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.agent",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.clientType",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.clientName",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.clientShortName",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.clientVersion",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.clientEngine",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.osName",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.osShortName",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.osVersion",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.osPlatform",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.deviceName",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.brandName",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.model",
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
     *      "LoginFailureLog",
     *      "LoginFailureLog.timestamp",
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
     * @return LogLoginFailure
     */
    public function setUser(User $user): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setIp(string $ip): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setHost(string $host): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setAgent(string $agent): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setClientType(string $clientType = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setClientName(string $clientName = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setClientShortName(string $clientShortName = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setClientVersion(string $clientVersion = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setClientEngine(string $clientEngine = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setOsName(string $osName = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setOsShortName(string $osShortName = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setOsVersion(string $osVersion = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setOsPlatform(string $osPlatform = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setDeviceName(string $deviceName = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setBrandName(string $brandName = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setModel(string $model = null): LogLoginFailure
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
     * @return LogLoginFailure
     */
    public function setTimestamp(\DateTime $timestamp): LogLoginFailure
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
