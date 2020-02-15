<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogRequest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\LogEntityTrait;
use App\Entity\Traits\LogRequestProcessRequestTrait;
use App\Entity\Traits\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
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
class LogRequest implements EntityInterface
{
    // Traits
    use LogEntityTrait;
    use LogRequestProcessRequestTrait;
    use Uuid;

    /**
     * @var UuidInterface
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.id",
     *
     *      "ApiKey.logsRequest",
     *      "User.logRequest",
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
    private UuidInterface $id;

    /**
     * @var User|null
     *
     * @Groups({
     *      "LogRequest.user",
     *  })
     *
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
    private ?User $user = null;

    /**
     * @var ApiKey|null
     *
     * @Groups({
     *      "LogRequest.apiKey",
     *  })
     *
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
    private ?ApiKey $apiKey;

    /**
     * @var int
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.statusCode",
     *  })
     *
     * @ORM\Column(
     *      name="status_code",
     *      type="integer",
     *      nullable=false,
     *  )
     */
    private int $statusCode;

    /**
     * @var int
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.responseContentLength",
     *  })
     *
     * @ORM\Column(
     *      name="response_content_length",
     *      type="integer",
     *      nullable=false,
     *  )
     */
    private int $responseContentLength;

    /**
     * @var bool
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.isMasterRequest",
     *  })
     *
     * @ORM\Column(
     *      name="is_master_request",
     *      type="boolean",
     *      nullable=false,
     *  )
     */
    private bool $masterRequest;

    private array $sensitiveProperties;

    /**
     * LogRequest constructor.
     *
     * @param array         $sensitiveProperties
     * @param Request|null  $request
     * @param Response|null $response
     * @param User|null     $user
     * @param ApiKey|null   $apiKey
     * @param bool          $masterRequest
     *
     * @throws Throwable
     */
    public function __construct(
        array $sensitiveProperties,
        ?Request $request = null,
        ?Response $response = null,
        ?User $user = null,
        ?ApiKey $apiKey = null,
        ?bool $masterRequest = null
    ) {
        $this->sensitiveProperties = $sensitiveProperties;
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

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id->toString();
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return int
     */
    public function getResponseContentLength(): int
    {
        return $this->responseContentLength;
    }

    /**
     * @return ApiKey|null
     */
    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    /**
     * @return bool
     */
    public function isMasterRequest(): bool
    {
        return $this->masterRequest;
    }

    /**
     * @return array
     */
    public function getSensitiveProperties(): array
    {
        return $this->sensitiveProperties;
    }

    /**
     * @param Response $response
     */
    private function processResponse(Response $response): void
    {
        $content = $response->getContent();

        $this->statusCode = $response->getStatusCode();
        $this->responseContentLength = $content === false ? 0 : mb_strlen($content);
    }
}
