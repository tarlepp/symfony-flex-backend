<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogLogin.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\LogEntityTrait;
use App\Entity\Traits\Uuid;
use DeviceDetector\DeviceDetector;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
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
 *      },
 *  )
 * @ORM\Entity(
 *      readOnly=true,
 *  )
 * @ORM\HasLifecycleCallbacks()
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LogLogin implements EntityInterface
{
    use LogEntityTrait;
    use Uuid;

    /**
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     *
     * @OA\Property(type="string", format="uuid")
     */
    #[Groups([
        'LogLogin',
        'LogLogin.id',
    ])]
    private UuidInterface $id;

    /**
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
    #[Groups([
        'LogLogin',
        'LogLogin.user',
    ])]
    private ?User $user;

    /**
     * @ORM\Column(
     *      name="type",
     *      type="EnumLogLogin",
     *      nullable=false,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.type',
    ])]
    private string $type;

    /**
     * @ORM\Column(
     *      name="client_type",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.clientType',
    ])]
    private ?string $clientType = null;

    /**
     * @ORM\Column(
     *      name="client_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.clientName',
    ])]
    private ?string $clientName = null;

    /**
     * @ORM\Column(
     *      name="client_short_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.clientShortName',
    ])]
    private ?string $clientShortName = null;

    /**
     * @ORM\Column(
     *      name="client_version",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.clientVersion',
    ])]
    private ?string $clientVersion = null;

    /**
     * @ORM\Column(
     *      name="client_engine",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.clientEngine',
    ])]
    private ?string $clientEngine = null;

    /**
     * @ORM\Column(
     *      name="os_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.osName',
    ])]
    private ?string $osName = null;

    /**
     * @ORM\Column(
     *      name="os_short_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.osShortName',
    ])]
    private ?string $osShortName = null;

    /**
     * @ORM\Column(
     *      name="os_version",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.osVersion',
    ])]
    private ?string $osVersion = null;

    /**
     * @ORM\Column(
     *      name="os_platform",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.osPlatform',
    ])]
    private ?string $osPlatform = null;

    /**
     * @ORM\Column(
     *      name="device_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.deviceName',
    ])]
    private ?string $deviceName = null;

    /**
     * @ORM\Column(
     *      name="brand_name",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.brandName',
    ])]
    private ?string $brandName = null;

    /**
     * @ORM\Column(
     *      name="model",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    #[Groups([
        'LogLogin',
        'LogLogin.model',
    ])]
    private ?string $model = null;

    private DeviceDetector $deviceDetector;

    /**
     * LogLogin constructor.
     *
     * @throws Throwable
     */
    public function __construct(string $type, Request $request, DeviceDetector $deviceDetector, ?User $user = null)
    {
        $this->id = $this->createUuid();

        $this->type = $type;
        $this->deviceDetector = $deviceDetector;
        $this->user = $user;

        $this->processTimeAndDate();
        $this->processRequestData($request);
        $this->processClientData();
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClientType(): ?string
    {
        return $this->clientType;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function getClientShortName(): ?string
    {
        return $this->clientShortName;
    }

    public function getClientVersion(): ?string
    {
        return $this->clientVersion;
    }

    public function getClientEngine(): ?string
    {
        return $this->clientEngine;
    }

    public function getOsName(): ?string
    {
        return $this->osName;
    }

    public function getOsShortName(): ?string
    {
        return $this->osShortName;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function getOsPlatform(): ?string
    {
        return $this->osPlatform;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

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

    private function getClientData(string $method, string $attribute): string
    {
        /** @var string|array<int, string> $value */
        $value = $this->deviceDetector->{$method}($attribute);

        return is_array($value) ? implode(', ', $value) : $value;
    }
}
