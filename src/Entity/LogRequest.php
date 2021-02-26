<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogRequest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\LogEntityTrait;
use App\Entity\Traits\LogRequestProcessRequestTrait;
use App\Entity\Traits\Uuid;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use function mb_strlen;

/**
 * Class LogRequest
 *
 * @ORM\Table(
 *      name="log_request",
 *      indexes={
 *          @ORM\Index(name="user_id", columns={"user_id"}),
 *          @ORM\Index(name="api_key_id", columns={"api_key_id"}),
 *          @ORM\Index(name="request_date", columns={"date"}),
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
class LogRequest implements EntityInterface
{
    use LogEntityTrait;
    use LogRequestProcessRequestTrait;
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
        'LogRequest',
        'LogRequest.id',

        'ApiKey.logsRequest',
        'User.logRequest',
    ])]
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\User",
     *      inversedBy="logsRequest",
     *  )
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(
     *          name="user_id",
     *          referencedColumnName="id",
     *          nullable=true,
     *          onDelete="SET NULL",
     *      ),
     *  })
     */
    #[Groups([
        'LogRequest.user',
    ])]
    private ?User $user;

    /**
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\ApiKey",
     *      inversedBy="logsRequest",
     *  )
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(
     *          name="api_key_id",
     *          referencedColumnName="id",
     *          nullable=true,
     *          onDelete="SET NULL",
     *      ),
     *  })
     */
    #[Groups([
        'LogRequest.apiKey',
    ])]
    private ?ApiKey $apiKey;

    /**
     * @ORM\Column(
     *      name="status_code",
     *      type="integer",
     *      nullable=false,
     *  )
     */
    #[Groups([
        'LogRequest',
        'LogRequest.statusCode',
    ])]
    private int $statusCode = 0;

    /**
     * @ORM\Column(
     *      name="response_content_length",
     *      type="integer",
     *      nullable=false,
     *  )
     */
    #[Groups([
        'LogRequest',
        'LogRequest.responseContentLength',
    ])]
    private int $responseContentLength = 0;

    /**
     * @ORM\Column(
     *      name="is_master_request",
     *      type="boolean",
     *      nullable=false,
     *  )
     */
    #[Groups([
        'LogRequest',
        'LogRequest.isMasterRequest',
    ])]
    private bool $masterRequest;

    /**
     * LogRequest constructor.
     *
     * @param array<int, string> $sensitiveProperties
     *
     * @throws Throwable
     */
    public function __construct(
        private array $sensitiveProperties,
        ?Request $request = null,
        ?Response $response = null,
        ?User $user = null,
        ?ApiKey $apiKey = null,
        ?bool $masterRequest = null
    ) {
        $this->id = $this->createUuid();
        $this->user = $user;
        $this->apiKey = $apiKey;
        $this->masterRequest = $masterRequest ?? true;

        $this->processTimeAndDate();

        if ($request !== null) {
            $this->processRequestData($request);
            $this->processRequest($request);
        }

        if ($response !== null) {
            $this->processResponse($response);
        }
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

    public function isMasterRequest(): bool
    {
        return $this->masterRequest;
    }

    public function getSensitiveProperties(): array
    {
        return $this->sensitiveProperties;
    }

    private function processResponse(Response $response): void
    {
        $content = $response->getContent();

        $this->statusCode = $response->getStatusCode();
        $this->responseContentLength = $content === false ? 0 : mb_strlen($content);
    }
}
