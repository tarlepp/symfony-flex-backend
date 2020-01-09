<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogLogin.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\LogEntityTrait;
use App\Entity\Traits\Uuid;
use DeviceDetector\DeviceDetector;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use function implode;
use function is_array;

/**
 * Class LogLogin
 *
 * @ORM\Table(
 *      name="log_login",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *          @ORM\Index(name="date", columns={"date"}),
 *      }
 *  )
 * @ORM\Entity(
 *      readOnly=true
 *  )
 * @ORM\HasLifecycleCallbacks()
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLogin implements EntityInterface
{
    // Traits
    use LogEntityTrait;
    use Uuid;

    /**
     * @var User|null
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
    protected $user;

    /**
     * @var UuidInterface
     *
     * @Groups({
     *      "LogLogin",
     *      "LogLogin.id",
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
     *      type="EnumLogLogin",
     *      nullable=false,
     *  )
     */
    private $type;

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
     * @var DeviceDetector
     */
    private $deviceDetector;

    /**
     * LogLogin constructor.
     *
     * @param string         $type
     * @param Request        $request
     * @param DeviceDetector $deviceDetector
     * @param User|null      $user
     *
     * @throws Throwable
     */
    public function __construct(string $type, Request $request, DeviceDetector $deviceDetector, ?User $user = null)
    {
        $this->id = $this->getUuid();

        $this->type = $type;
        $this->deviceDetector = $deviceDetector;
        $this->user = $user;

        $this->processTimeAndDate();
        $this->processRequestData($request);
        $this->processClientData();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id->toString();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getClientType(): ?string
    {
        return $this->clientType;
    }

    /**
     * @return string|null
     */
    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    /**
     * @return string|null
     */
    public function getClientShortName(): ?string
    {
        return $this->clientShortName;
    }

    /**
     * @return string|null
     */
    public function getClientVersion(): ?string
    {
        return $this->clientVersion;
    }

    /**
     * @return string|null
     */
    public function getClientEngine(): ?string
    {
        return $this->clientEngine;
    }

    /**
     * @return string|null
     */
    public function getOsName(): ?string
    {
        return $this->osName;
    }

    /**
     * @return string|null
     */
    public function getOsShortName(): ?string
    {
        return $this->osShortName;
    }

    /**
     * @return string|null
     */
    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    /**
     * @return string|null
     */
    public function getOsPlatform(): ?string
    {
        return $this->osPlatform;
    }

    /**
     * @return string|null
     */
    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    /**
     * @return string|null
     */
    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    /**
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    private function processClientData(): void
    {
        $this->clientType = $this->getClientData('getClient', 'type');
        $this->clientName = $this->getClientData('getClient', 'name');
        $this->clientShortName = $this->getClientData('getClient', 'short_name');
        $this->clientVersion = $this->getClientData('getClient', 'version');
        $this->clientEngine = $this->getClientData('getClient', 'engine');
        $this->osName = $this->getClientData('getOs', 'name');
        $this->osShortName = $this->getClientData('getOs', 'short_name');
        $this->osVersion = $this->getClientData('getOs', 'version');
        $this->osPlatform = $this->getClientData('getOs', 'platform');
        $this->deviceName = $this->deviceDetector->getDeviceName();
        $this->brandName = $this->deviceDetector->getBrandName();
        $this->model = $this->deviceDetector->getModel();
    }

    /**
     * @param string $method
     * @param string $attribute
     *
     * @return string
     */
    private function getClientData(string $method, string $attribute): string
    {
        /** @var string|array $value */
        $value = $this->deviceDetector->{$method}($attribute);

        return is_array($value) ? implode(', ', $value) : (string)$value;
    }
}
