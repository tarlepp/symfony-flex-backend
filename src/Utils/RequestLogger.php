<?php
declare(strict_types = 1);
/**
 * /src/Utils/RequestLogger.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @property  array<int, string> $sensitiveProperties
 */
class RequestLogger implements RequestLoggerInterface
{
    private ?Response $response = null;
    private ?Request $request = null;
    private ?User $user = null;
    private ?ApiKey $apiKey = null;
    private bool $mainRequest = false;

    public function __construct(
        private LogRequestResource $resource,
        private LoggerInterface $logger,
        private array $sensitiveProperties,
    ) {
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

    public function setMainRequest(bool $mainRequest): self
    {
        $this->mainRequest = $mainRequest;

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
        /**
         * We want to clear possible existing managements entities before we
         * flush this new `LogRequest` entity to database. This is to prevent
         * not wanted entity state changes to be flushed.
         */
        $this->resource->getRepository()->getEntityManager()->clear();

        // Create new request log entity
        $entity = new LogRequest(
            $this->sensitiveProperties,
            $this->request,
            $this->response,
            $this->user,
            $this->apiKey,
            $this->mainRequest
        );

        $this->resource->save($entity, true, true);
    }
}
