<?php
declare(strict_types=1);
/**
 * /src/Entity/LogRequest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

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
 *      }
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\LogRequestRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogRequest implements EntityInterface
{
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
     * @var \App\Entity\User
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
     *      ),
     *  })
     */
    private $user;

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
     * @var \DateTime
     *
     * @Groups({
     *      "LogRequest",
     *      "LogRequest.time",
     *  })
     *
     * @ORM\Column(
     *      name="time",
     *      type="datetime",
     *      nullable=false,
     *  )
     */
    private $time;

    /**
     * LogRequest constructor.
     *
     * @param Request|null  $request
     * @param Response|null $response
     *
     * @throws \LogicException
     */
    public function __construct(Request $request = null, Response $response = null)
    {
        $this->id = Uuid::uuid4()->toString();

        if ($request !== null) {
            $this->setClientIp((string)$request->getClientIp());
            $this->setMethod($request->getRealMethod());
            $this->setScheme($request->getScheme());
            $this->setHttpHost($request->getHttpHost());
            $this->setBasePath($request->getBasePath());
            $this->setScript('/' . \basename($request->getScriptName()));
            $this->setPath($request->getPathInfo());
            $this->setQueryString($request->getRequestUri());
            $this->setUri($request->getUri());
            $this->setController($request->get('_controller', ''));
            $this->setAction($this->determineAction($request));
            $this->setHeaders($request->headers->all());
            $this->setContentType($request->getMimeType($request->getContentType()));
            $this->setContentTypeShort($request->getContentType());
            $this->setContent($request->getContent());
            $this->setParameters($this->determineParameters($request));
            $this->setXmlHttpRequest($request->isXmlHttpRequest());
            $this->setTime(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        if ($response !== null) {
            $this->setStatusCode($response->getStatusCode());
            $this->setResponseContentLength(mb_strlen($response->getContent()));
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
     * @param string $clientIp
     *
     * @return LogRequest
     */
    public function setClientIp(string $clientIp): LogRequest
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     *
     * @return LogRequest
     */
    public function setUri(string $uri): LogRequest
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return LogRequest
     */
    public function setMethod(string $method): LogRequest
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     *
     * @return LogRequest
     */
    public function setScheme(string $scheme): LogRequest
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @return string
     */
    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    /**
     * @param string $httpHost
     *
     * @return LogRequest
     */
    public function setHttpHost(string $httpHost): LogRequest
    {
        $this->httpHost = $httpHost;

        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     *
     * @return LogRequest
     */
    public function setBasePath(string $basePath): LogRequest
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    /**
     * @param string|null $queryString
     *
     * @return LogRequest
     */
    public function setQueryString(string $queryString = null): LogRequest
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return LogRequest
     */
    public function setHeaders(array $headers): LogRequest
    {
        // Clean possible sensitive data from parameters
        \array_walk($headers, [$this, 'cleanParameters']);

        $this->headers = $headers;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     *
     * @return LogRequest
     */
    public function setParameters(array $parameters): LogRequest
    {
        // Clean possible sensitive data from parameters
        \array_walk($parameters, [$this, 'cleanParameters']);

        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     *
     * @return LogRequest
     */
    public function setStatusCode(int $statusCode): LogRequest
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getResponseContentLength(): int
    {
        return $this->responseContentLength;
    }

    /**
     * @param int $responseContentLength
     *
     * @return LogRequest
     */
    public function setResponseContentLength(int $responseContentLength): LogRequest
    {
        $this->responseContentLength = $responseContentLength;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * @param \DateTime $time
     *
     * @return LogRequest
     */
    public function setTime(\DateTime $time): LogRequest
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return LogRequest
     */
    public function setUser(User $user = null): LogRequest
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isXmlHttpRequest(): bool
    {
        return $this->xmlHttpRequest;
    }

    /**
     * @param boolean $xmlHttpRequest
     *
     * @return LogRequest
     */
    public function setXmlHttpRequest(bool $xmlHttpRequest): LogRequest
    {
        $this->xmlHttpRequest = $xmlHttpRequest;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @param string|null $controller
     *
     * @return LogRequest
     */
    public function setController(string $controller = null): LogRequest
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string|null $action
     *
     * @return LogRequest
     */
    public function setAction(string $action = null): LogRequest
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     *
     * @return LogRequest
     */
    public function setPath(string $path = null): LogRequest
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMasterRequest(): bool
    {
        return $this->masterRequest;
    }

    /**
     * @param boolean $masterRequest
     *
     * @return LogRequest
     */
    public function setMasterRequest(bool $masterRequest): LogRequest
    {
        $this->masterRequest = $masterRequest;

        return $this;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @param string $script
     *
     * @return LogRequest
     */
    public function setScript(string $script): LogRequest
    {
        $this->script = $script;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return LogRequest
     */
    public function setContent(string $content): LogRequest
    {
        $this->content = $this->cleanContent($content);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     *
     * @return LogRequest
     */
    public function setContentType(string $contentType = null): LogRequest
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentTypeShort(): ?string
    {
        return $this->contentTypeShort;
    }

    /**
     * @param string $contentTypeShort
     *
     * @return LogRequest
     */
    public function setContentTypeShort(string $contentTypeShort = null): LogRequest
    {
        $this->contentTypeShort = $contentTypeShort;

        return $this;
    }

    /**
     * Helper method to clean parameters / header array of any sensitive data.
     *
     * @param mixed  $value
     * @param string $key
     */
    protected function cleanParameters(&$value, string $key): void
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
            $value = $replacements[$key];
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
            } catch (\LogicException $error) { // Oh noes content isn't JSON so just parse it
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
}
