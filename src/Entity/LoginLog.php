<?php
declare(strict_types=1);
/**
 * /src/Entity/LoginLog.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class LoginLog
 *
 * @ORM\Table(
 *      name="login_log",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *      }
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\LoginLogRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLog implements EntityInterface
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
     *      inversedBy="loginLogs",
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
     *      "LoginLog.loginTime",
     *  })
     *
     * @ORM\Column(
     *      name="login_time",
     *      type="datetime",
     *      nullable=false,
     *  )
     */
    private $loginTime;

    /**
     * LoginLog constructor.
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
     * @return LoginLog
     */
    public function setUser(User $user): LoginLog
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
     * @return LoginLog
     */
    public function setIp(string $ip): LoginLog
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
     * @return LoginLog
     */
    public function setHost(string $host): LoginLog
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
     * @return LoginLog
     */
    public function setAgent(string $agent): LoginLog
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
     * @return LoginLog
     */
    public function setClientType(string $clientType = null): LoginLog
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
     * @return LoginLog
     */
    public function setClientName(string $clientName = null): LoginLog
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
     * @return LoginLog
     */
    public function setClientShortName(string $clientShortName = null): LoginLog
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
     * @return LoginLog
     */
    public function setClientVersion(string $clientVersion = null): LoginLog
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
     * @return LoginLog
     */
    public function setClientEngine(string $clientEngine = null): LoginLog
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
     * @return LoginLog
     */
    public function setOsName(string $osName = null): LoginLog
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
     * @return LoginLog
     */
    public function setOsShortName(string $osShortName = null): LoginLog
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
     * @return LoginLog
     */
    public function setOsVersion(string $osVersion = null): LoginLog
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
     * @return LoginLog
     */
    public function setOsPlatform(string $osPlatform = null): LoginLog
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
     * @return LoginLog
     */
    public function setDeviceName(string $deviceName = null): LoginLog
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
     * @return LoginLog
     */
    public function setBrandName(string $brandName = null): LoginLog
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
     * @return LoginLog
     */
    public function setModel(string $model = null): LoginLog
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoginTime(): \DateTime
    {
        return $this->loginTime;
    }

    /**
     * @param \DateTime $loginTime
     *
     * @return LoginLog
     */
    public function setLoginTime(\DateTime $loginTime): LoginLog
    {
        $this->loginTime = $loginTime;

        return $this;
    }
}
