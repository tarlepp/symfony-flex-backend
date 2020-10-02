<?php
declare(strict_types = 1);
/**
 * /src/Utils/RequestLogger.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils;

use App\Entity\ApiKey;
use App\Entity\LogRequest;
use App\Entity\User;
use App\Resource\LogRequestResource;
use App\Utils\Interfaces\RequestLoggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class RequestLogger
 *
 * @package App\Services
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLogger implements RequestLoggerInterface
{
    private LogRequestResource $resource;
    private LoggerInterface $logger;
    private ?Response $response = null;
    private ?Request $request = null;
    private ?User $user = null;
    private ?ApiKey $apiKey = null;
    private bool $masterRequest = false;

    /**
     * @var array<int, string>
     */
    private array $sensitiveProperties;

    /**
     * ResponseLogger constructor.
     *
     * @param array<int, string> $sensitiveProperties
     */
    public function __construct(LogRequestResource $resource, LoggerInterface $logger, array $sensitiveProperties)
    {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->sensitiveProperties = $sensitiveProperties;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function setUser(?User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function setApiKey(?ApiKey $apiKey = null): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setMasterRequest(bool $masterRequest): self
    {
        $this->masterRequest = $masterRequest;

        return $this;
    }

    public function handle(): void
    {
        // Just check that we have all that we need
        if (!($this->request instanceof Request) || !($this->response instanceof Response)) {
            return;
        }

        try {
            $this->createRequestLogEntry();
        } catch (Throwable $error) {
            $this->logger->error($error->getMessage());
        }
    }

    /**
     * Store request log to database.
     *
     * @throws Throwable
     */
    private function createRequestLogEntry(): void
    {
        // Create new request log entity
        $entity = new LogRequest(
            $this->sensitiveProperties,
            $this->request,
            $this->response,
            $this->user,
            $this->apiKey,
            $this->masterRequest
        );

        $this->resource->save($entity, true, true);
    }
}
