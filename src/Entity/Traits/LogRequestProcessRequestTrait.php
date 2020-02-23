<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/LogRequestProcessRequestTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use App\Utils\JSON;
use JsonException;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_walk;
use function basename;
use function explode;
use function is_array;
use function mb_strtolower;
use function parse_str;
use function preg_replace;
use function strpos;

/**
 * Trait LogRequestProcessRequestTrait
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method array getSensitiveProperties();
 */
trait LogRequestProcessRequestTrait
{
    private string $replaceValue = '*** REPLACED ***';

    /**
     * @var mixed[]
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
    private array $headers = [];

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
    private string $method = '';

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
    private string $scheme = '';

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
    private string $basePath = '';

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
    private string $script = '';

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
    private string $path = '';

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
    private string $queryString = '';

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
    private string $uri = '';

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
    private string $controller = '';

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
    private string $contentType = '';

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
    private string $contentTypeShort = '';

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
    private bool $xmlHttpRequest = false;

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
    private string $action = '';

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
    private string $content = '';

    /**
     * @var mixed[]
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
    private array $parameters = [];

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
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return bool
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
     * @throws LogicException
     */
    protected function processRequest(Request $request): void
    {
        $this->processRequestBaseInfo($request);
        $this->processHeadersAndParameters($request);

        $this->action = $this->determineAction($request);
        $this->content = $this->cleanContent((string)$request->getContent());
    }

    /**
     * @param Request $request
     *
     * @throws LogicException
     */
    private function processHeadersAndParameters(Request $request): void
    {
        $rawHeaders = $request->headers->all();

        // Clean possible sensitive data from parameters
        array_walk(
            $rawHeaders,
            /**
             * @param mixed  $value
             * @param string $key
             */
            function (&$value, string $key): void {
                $this->cleanParameters($value, $key);
            }
        );

        $this->headers = $rawHeaders;

        $rawParameters = $this->determineParameters($request);

        // Clean possible sensitive data from parameters
        array_walk(
            $rawParameters,
            /**
             * @param mixed  $value
             * @param string $key
             */
            function (&$value, string $key): void {
                $this->cleanParameters($value, $key);
            }
        );

        $this->parameters = $rawParameters;
    }

    /**
     * @param Request $request
     */
    private function processRequestBaseInfo(Request $request): void
    {
        $this->method = $request->getRealMethod();
        $this->scheme = $request->getScheme();
        $this->basePath = $request->getBasePath();
        $this->script = '/' . basename($request->getScriptName());
        $this->path = $request->getPathInfo();
        $this->queryString = $request->getRequestUri();
        $this->uri = $request->getUri();
        $this->controller = (string)$request->get('_controller', '');
        $this->contentType = (string)$request->getMimeType($request->getContentType() ?? '');
        $this->contentTypeShort = (string)$request->getContentType();
        $this->xmlHttpRequest = $request->isXmlHttpRequest();
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function determineAction(Request $request): string
    {
        $rawAction = (string)$request->get('_controller', '');
        $rawAction = explode(strpos($rawAction, '::') ? '::' : ':', $rawAction);

        return $rawAction[1] ?? '';
    }

    /**
     * Getter method to convert current request parameters to array.
     *
     * @param Request $request
     *
     * @return mixed[]
     *
     * @throws LogicException
     */
    private function determineParameters(Request $request): array
    {
        $rawContent = (string)$request->getContent();

        // By default just get whole parameter bag
        $output = $request->request->all();

        // Content given so parse it
        if ($rawContent) {
            // First try to convert content to array from JSON
            try {
                /** @var array<string, mixed> $output */
                $output = JSON::decode($rawContent, true);
            } catch (JsonException $error) {
                (static fn (Throwable $error): Throwable => $error)($error);

                // Oh noes content isn't JSON so just parse it
                $output = [];

                parse_str($rawContent, $output);
            }
        }

        return (array)$output;
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
        $replacements = array_fill_keys($this->sensitiveProperties, $this->replaceValue);

        // Normalize current key
        $key = mb_strtolower($key);

        // Replace current value
        if (array_key_exists($key, $replacements)) {
            $value = $this->cleanContent((string)$replacements[$key]);
        }

        // Recursive call
        if (is_array($value)) {
            array_walk(
                $value,
                /**
                 * @param mixed  $value
                 * @param string $key
                 */
                function (&$value, string $key): void {
                    $this->cleanParameters($value, $key);
                }
            );
        }
    }

    /**
     * Method to clean raw request content of any sensitive data.
     *
     * @param string $inputContent
     *
     * @return string
     */
    private function cleanContent(string $inputContent): string
    {
        $iterator = static function (string $search) use (&$inputContent): void {
            $inputContent = (string)preg_replace(
                '/(' . $search . '":)\s*"(.*)"/',
                '$1"*** REPLACED ***"',
                $inputContent
            );
        };

        array_map($iterator, $this->getSensitiveProperties());

        return $inputContent;
    }
}
