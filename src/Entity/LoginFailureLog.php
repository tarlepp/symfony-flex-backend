<?php
declare(strict_types=1);
/**
 * /src/Entity/LoginFailureLog.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class LoginFailureLog
 *
 * @ORM\Table(
 *      name="login_failure_log",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *      }
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\LoginFailureLogRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginFailureLog implements EntityInterface
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
     *      inversedBy="loginFailureLogs",
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
     * LoginFailureLog constructor.
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
     * @return LoginFailureLog
     */
    public function setUser(User $user): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setIp(string $ip): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setHost(string $host): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setAgent(string $agent): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setClientType(string $clientType = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setClientName(string $clientName = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setClientShortName(string $clientShortName = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setClientVersion(string $clientVersion = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setClientEngine(string $clientEngine = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setOsName(string $osName = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setOsShortName(string $osShortName = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setOsVersion(string $osVersion = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setOsPlatform(string $osPlatform = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setDeviceName(string $deviceName = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setBrandName(string $brandName = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setModel(string $model = null): LoginFailureLog
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
     * @return LoginFailureLog
     */
    public function setTimestamp(\DateTime $timestamp): LoginFailureLog
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
