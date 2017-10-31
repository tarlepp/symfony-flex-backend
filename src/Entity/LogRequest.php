<?php
declare(strict_types = 1);
/**
 * /src/Entity/LogRequest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use App\Entity\Traits\LogEntityTrait;
use App\Utils\JSON;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Groups;

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

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.id",
     *      "User.logRequest",
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
     * @var \App\Entity\User|null
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
    private $user;

    /**
     * @var \App\Entity\ApiKey|null
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
    private $apiKey;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.clientIp",
     *  })
     *
     * @ORM\Column(
     *      name="client_ip",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $clientIp;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.method",
     *  })
     *
     * @ORM\Column(
     *      name="method",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $method;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.scheme",
     *  })
     *
     * @ORM\Column(
     *      name="scheme",
     *      type="string",
     *      length=5,
     *      nullable=false,
     *  )
     */
    private $scheme;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.httpHost",
     *  })
     *
     * @ORM\Column(
     *      name="http_host",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $httpHost;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.basePath",
     *  })
     *
     * @ORM\Column(
     *      name="base_path",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $basePath;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.script",
     *  })
     *
     * @ORM\Column(
     *      name="script",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private $script;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.path",
     *  })
     *
     * @ORM\Column(
     *      name="path",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $path;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.queryString",
     *  })
     *
     * @ORM\Column(
     *      name="query_string",
     *      type="text",
     *      nullable=true,
     *  )
     */
    private $queryString;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.uri",
     *  })
     *
     * @ORM\Column(
     *      name="uri",
     *      type="text",
     *      nullable=false,
     *  )
     */
    private $uri;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.controller",
     *  })
     *
     * @ORM\Column(
     *      name="controller",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $controller;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.action",
     *  })
     *
     * @ORM\Column(
     *      name="action",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $action;

    /**
     * @var array
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.headers",
     *  })
     *
     * @ORM\Column(
     *      name="headers",
     *      type="array",
     *  )
     */
    private $headers;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.contentType",
     *  })
     *
     * @ORM\Column(
     *      name="content_type",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $contentType;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.contentTypeShort",
     *  })
     *
     * @ORM\Column(
     *      name="content_type_short",
     *      type="string",
     *      length=255,
     *      nullable=true,
     *  )
     */
    private $contentTypeShort;

    /**
     * @var string
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.content",
     *  })
     *
     * @ORM\Column(
     *      name="content",
     *      type="text",
     *      nullable=true,
     *  )
     */
    private $content;

    /**
     * @var array
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.parameters",
     *  })
     *
     * @ORM\Column(
     *      name="parameters",
     *      type="array",
     *  )
     */
    private $parameters;

    /**
     * @var integer
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
    private $statusCode;

    /**
     * @var integer
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
    private $responseContentLength;

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
    private $masterRequest;

    /**
     * @var bool
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.isXmlHttpRequest",
     *  })
     *
     * @ORM\Column(
     *      name="is_xml_http_request",
     *      type="boolean",
     *      nullable=false,
     *  )
     */
    private $xmlHttpRequest;

    /**
     * LogRequest constructor.
     *
     * @param Request|null  $request
     * @param Response|null $response
     * @param User|null     $user
     * @param ApiKey|null   $apiKey
     * @param bool          $masterRequest
     *
     * @throws \LogicException
     */
    public function __construct(
        Request $request = null,
        Response $response = null,
        User $user = null,
        ApiKey $apiKey = null,
        bool $masterRequest = null
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->apiKey = $apiKey;
        $this->masterRequest = $masterRequest ?? true;

        $this->processTimeAndDate();

        if ($request !== null) {
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
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return string|null
     */
    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
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
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return ApiKey|null
     */
    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    /**
     * @return boolean
     */
    public function isXmlHttpRequest(): bool
    {
        return $this->xmlHttpRequest;
    }

    /**
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return boolean
     */
    public function isMasterRequest(): bool
    {
        return $this->masterRequest;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @return string|null
     */
    public function getContentTypeShort(): ?string
    {
        return $this->contentTypeShort;
    }

    /**
     * @param Request $request
     *
     * @throws \LogicException
     */
    private function processRequest(Request $request): void
    {
        $this->processRequestBaseInfo($request);
        $this->processHeadersAndParameters($request);

        $this->action = $this->determineAction($request);
        $this->content = $this->cleanContent($request->getContent());
    }

    /**
     * Helper method to clean parameters / header array of any sensitive data.
     *
     * @param mixed  $value
     * @param string $key
     */
    private function cleanParameters(&$value, string $key): void
    {
        // What keys we should replace so that any sensitive data is not logged
        static $replacements = [
            'password'          => '*** REPLACED ***',
            'token'             => '*** REPLACED ***',
            'authorization'     => '*** REPLACED ***',
            'cookie'            => '*** REPLACED ***',
        ];

        // Normalize current key
        $key = \mb_strtolower($key);

        // Replace current value
        if (\array_key_exists($key, $replacements)) {
            $value = $this->cleanContent($replacements[$key]);
        }

        // Recursive call
        if (\is_array($value)) {
            \array_walk($value, [$this, 'cleanParameters']);
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function determineAction(Request $request): string
    {
        $action = $request->get('_controller', '');
        $action = \strpos($action, '::') ? \explode('::', $action) : \explode(':', $action);

        return $action[1] ?? '';
    }

    /**
     * Getter method to convert current request parameters to array.
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws \LogicException
     */
    private function determineParameters(Request $request): array
    {
        // Content given so parse it
        if ($request->getContent()) {
            // First try to convert content to array from JSON
            try {
                $output = JSON::decode($request->getContent(), true);
            } /** @noinspection BadExceptionsProcessingInspection */
            catch (\LogicException $error) { // Oh noes content isn't JSON so just parse it
                $output = [];

                \parse_str($request->getContent(), $output);
            }
        } else { // Otherwise trust parameter bag
            $output = $request->request->all();
        }

        return (array)$output;
    }

    /**
     * Method to clean raw request content of any sensitive data.
     *
     * @param string $content
     *
     * @return string
     */
    private function cleanContent(string $content): string
    {
        $iterator = function ($search) use (&$content) {
            $content = \preg_replace('/(' . $search . '":)\s*"(.*)"/', '$1"*** REPLACED ***"', $content);
        };

        static $replacements = [
            'password',
            'token',
            'authorization',
            'cookie'
        ];

        \array_map($iterator, $replacements);

        return $content;
    }

    /**
     * @param Request $request
     *
     * @throws \LogicException
     */
    private function processHeadersAndParameters(Request $request): void
    {
        $headers = $request->headers->all();

        // Clean possible sensitive data from parameters
        \array_walk($headers, [$this, 'cleanParameters']);

        $this->headers = $headers;

        $parameters = $this->determineParameters($request);

        // Clean possible sensitive data from parameters
        \array_walk($parameters, [$this, 'cleanParameters']);

        $this->parameters = $parameters;
    }

    /**
     * @param Request $request
     */
    private function processRequestBaseInfo(Request $request): void
    {
        $this->clientIp = (string)$request->getClientIp();
        $this->method = $request->getRealMethod();
        $this->scheme = $request->getScheme();
        $this->httpHost = $request->getHttpHost();
        $this->basePath = $request->getBasePath();
        $this->script = '/' . \basename($request->getScriptName());
        $this->path = $request->getPathInfo();
        $this->queryString = $request->getRequestUri();
        $this->uri = $request->getUri();
        $this->controller = $request->get('_controller', '');
        $this->contentType = (string)$request->getMimeType($request->getContentType());
        $this->contentTypeShort = (string)$request->getContentType();
        $this->xmlHttpRequest = $request->isXmlHttpRequest();
    }

    /**
     * @param Response $response
     */
    private function processResponse(Response $response): void
    {
        $this->statusCode = $response->getStatusCode();
        $this->responseContentLength = \mb_strlen($response->getContent());
    }
}
