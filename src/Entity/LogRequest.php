<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogRequest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\LogRequestProcessRequestTrait;
use App\Entity\Traits\Uuid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use function mb_strlen;

/**
 * Class LogRequest
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity(
    readOnly: true,
)]
#[ORM\Table(
    name: 'log_request',
)]
#[ORM\Index(
    columns: [
        'user_id',
    ],
    name: 'user_id',
)]
#[ORM\Index(
    columns: [
        'api_key_id',
    ],
    name: 'api_key_id',
)]
#[ORM\Index(
    columns: [
        'date',
    ],
    name: 'request_date',
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LogRequest extends LogEntity implements EntityInterface
{
    use LogRequestProcessRequestTrait;
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
        'LogRequest',
        'LogRequest.id',

        'ApiKey.logsRequest',
        'User.logsRequest',
    ])]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'status_code',
        type: Types::INTEGER,
    )]
    #[Groups([
        'LogRequest',
        'LogRequest.statusCode',
    ])]
    private readonly int $statusCode;

    #[ORM\Column(
        name: 'response_content_length',
        type: Types::INTEGER,
    )]
    #[Groups([
        'LogRequest',
        'LogRequest.responseContentLength',
    ])]
    private readonly int $responseContentLength;

    /**
     * LogRequest constructor.
     *
     * @param array<int, string> $sensitiveProperties
     *
     * @throws Throwable
     */
    public function __construct(
        private readonly array $sensitiveProperties,
        readonly Request|null $request = null,
        readonly Response|null $response = null,
        #[ORM\ManyToOne(
            targetEntity: User::class,
            inversedBy: 'logsRequest',
        )]
        #[ORM\JoinColumn(
            name: 'user_id',
            onDelete: 'SET NULL',
        )]
        #[Groups([
            'LogRequest.user',
        ])]
        private readonly ?User $user = null,
        #[ORM\ManyToOne(
            targetEntity: ApiKey::class,
            inversedBy: 'logsRequest',
        )]
        #[ORM\JoinColumn(
            name: 'api_key_id',
            onDelete: 'SET NULL',
        )]
        #[Groups([
            'LogRequest.apiKey',
        ])]
        private readonly ?ApiKey $apiKey = null,
        #[ORM\Column(
            name: 'is_main_request',
            type: Types::BOOLEAN,
        )]
        #[Groups([
            'LogRequest',
            'LogRequest.isMainRequest',
        ])]
        private readonly bool $mainRequest = true,
    ) {
        parent::__construct($request);

        $this->id = $this->createUuid();

        // Change this after - https://github.com/phpstan/phpstan/issues/6402 - is solved
        if ($request !== null) {
            $this->action = $this->determineAction($request);
            $this->content = $this->cleanContent($request->getContent());
            $this->method = $request->getRealMethod();
            $this->scheme = $request->getScheme();
            $this->basePath = $request->getBasePath();
            $this->script = '/' . basename($request->getScriptName());
            $this->path = $request->getPathInfo();
            $this->queryString = $request->getRequestUri();
            $this->uri = $request->getUri();
            $this->controller = (string)$request->attributes->get('_controller', '');
            $this->contentType = (string)$request->getMimeType($request->getContentType() ?? '');
            $this->contentTypeShort = (string)$request->getContentType();
            $this->xmlHttpRequest = $request->isXmlHttpRequest();
            $this->headers = $this->processHeaders($request);
            $this->parameters = $this->processParameters($request);
        }

        $content = $response?->getContent() ?? false;
        $this->statusCode = $response?->getStatusCode() ?? 0;
        $this->responseContentLength = $content === false ? 0 : mb_strlen($content);
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseContentLength(): int
    {
        return $this->responseContentLength;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    public function isMainRequest(): bool
    {
        return $this->mainRequest;
    }

    /**
     * @return array<int, string>
     */
    public function getSensitiveProperties(): array
    {
        return $this->sensitiveProperties;
    }
}
