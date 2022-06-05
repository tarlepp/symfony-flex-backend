<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogLogin.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Doctrine\DBAL\Types\Types as AppTypes;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Uuid;
use DeviceDetector\DeviceDetector;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use function implode;
use function is_array;

/**
 * Class LogLogin
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity(
    readOnly: true,
)]
#[ORM\Table(
    name: 'log_login',
)]
#[ORM\Index(
    columns: [
        'user_id',
    ],
    name: 'user_id',
)]
#[ORM\Index(
    columns: [
        'date',
    ],
    name: 'date',
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LogLogin extends LogEntity implements EntityInterface
{
    use Uuid;

    /**
     * @OA\Property(type="string", format="uuid")
     */
    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.id',
    ])]
    private readonly UuidInterface $id;

    #[ORM\Column(
        name: 'username',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.username',
    ])]
    private readonly string $username;

    #[ORM\Column(
        name: 'client_type',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.clientType',
    ])]
    private readonly string $clientType;

    #[ORM\Column(
        name: 'client_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.clientName',
    ])]
    private readonly string $clientName;

    #[ORM\Column(
        name: 'client_short_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.clientShortName',
    ])]
    private readonly string $clientShortName;

    #[ORM\Column(
        name: 'client_version',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.clientVersion',
    ])]
    private readonly string $clientVersion;

    #[ORM\Column(
        name: 'client_engine',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.clientEngine',
    ])]
    private readonly string $clientEngine;

    #[ORM\Column(
        name: 'os_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.osName',
    ])]
    private readonly string $osName;

    #[ORM\Column(
        name: 'os_short_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.osShortName',
    ])]
    private readonly string $osShortName;

    #[ORM\Column(
        name: 'os_version',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.osVersion',
    ])]
    private readonly string $osVersion;

    #[ORM\Column(
        name: 'os_platform',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.osPlatform',
    ])]
    private readonly string $osPlatform;

    #[ORM\Column(
        name: 'device_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.deviceName',
    ])]
    private readonly string $deviceName;

    #[ORM\Column(
        name: 'brand_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.brandName',
    ])]
    private readonly string $brandName;

    #[ORM\Column(
        name: 'model',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.model',
    ])]
    private readonly string $model;

    /**
     * LogLogin constructor.
     *
     * @throws Throwable
     */
    public function __construct(
        #[ORM\Column(
            name: 'type',
            type: AppTypes::ENUM_LOG_LOGIN,
        )]
        #[Groups([
            'LogLogin',
            'LogLogin.type',
        ])]
        private readonly string $type,
        readonly Request $request,
        private readonly DeviceDetector $deviceDetector,
        #[ORM\ManyToOne(
            targetEntity: User::class,
            inversedBy: 'logsLogin',
        )]
        #[ORM\JoinColumn(
            name: 'user_id',
            onDelete: 'SET NULL',
        )]
        #[Groups([
            'LogLogin',
            'LogLogin.user',
        ])]
        private readonly ?User $user = null
    ) {
        parent::__construct($request);

        $this->id = $this->createUuid();
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
        $this->username = $this->user?->getUsername() ?? '';
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

    public function getUsername(): string
    {
        return $this->username;
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

    private function getClientData(string $method, string $attribute): string
    {
        /** @var string|array<int, string> $value */
        $value = $this->deviceDetector->{$method}($attribute);

        return is_array($value) ? implode(', ', $value) : $value;
    }
}
