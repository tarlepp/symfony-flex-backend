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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLogger implements RequestLoggerInterface
{
    private LogRequestResource $resource;
    private LoggerInterface $logger;
    private array $sensitiveProperties;
    private ?Response $response = null;
    private ?Request $request = null;
    private ?User $user = null;
    private ?ApiKey $apiKey = null;
    private bool $masterRequest = false;

    /**
     * ResponseLogger constructor.
     *
     * @param LogRequestResource $resource
     * @param LoggerInterface    $logger
     * @param array              $sensitiveProperties
     */
    public function __construct(LogRequestResource $resource, LoggerInterface $logger, array $sensitiveProperties)
    {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->sensitiveProperties = $sensitiveProperties;
    }

    /**
     * Setter for response object.
     *
     * @param Response $response
     *
     * @return RequestLoggerInterface
     */
    public function setResponse(Response $response): RequestLoggerInterface
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Setter for request object.
     *
     * @param Request $request
     *
     * @return RequestLoggerInterface
     */
    public function setRequest(Request $request): RequestLoggerInterface
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Setter method for current user.
     *
     * @param User|null $user
     *
     * @return RequestLoggerInterface
     */
    public function setUser(?User $user = null): RequestLoggerInterface
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Setter method for current api key
     *
     * @param ApiKey|null $apiKey
     *
     * @return RequestLoggerInterface
     */
    public function setApiKey(?ApiKey $apiKey = null): RequestLoggerInterface
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Setter method for 'master request' info.
     *
     * @param bool $masterRequest
     *
     * @return RequestLoggerInterface
     */
    public function setMasterRequest(bool $masterRequest): RequestLoggerInterface
    {
        $this->masterRequest = $masterRequest;

        return $this;
    }

    /**
     * Method to handle current response and log it to database.
     */
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
     * Store request log.
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
