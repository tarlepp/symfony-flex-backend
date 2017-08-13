<?php
declare(strict_types=1);
/**
 * /src/Services/RequestLogger.php
 *
 * @Book  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Services;

use App\Entity\RequestLog as RequestLogEntity;
use App\Resource\RequestLogResource;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RequestLogger
 *
 * @package App\Services
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class RequestLogger implements RequestLoggerInterface
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RequestLogResource
     */
    private $resource;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var bool
     */
    private $masterRequest;

    /**
     * ResponseLogger constructor.
     *
     * @param LoggerInterface    $logger
     * @param RequestLogResource $resource
     */
    public function __construct(LoggerInterface $logger, RequestLogResource $resource)
    {
        // Store user services
        $this->logger = $logger;
        $this->resource = $resource;
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
     * @param UserInterface|null $user
     *
     * @return RequestLoggerInterface
     */
    public function setUser(UserInterface $user = null): RequestLoggerInterface
    {
        $this->user = $user;

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
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        // Just check that we have all that we need
        if (!($this->request instanceof Request) || !($this->response instanceof Response)) {
            return;
        }

        try {
            $this->createRequestLogEntry();
        } catch (\Exception $error) {
            $this->logger->error($error->getMessage());
        }
    }

    /**
     * Store request log and  clean history
     *
     * @throws \LogicException
     */
    private function createRequestLogEntry()
    {
        // Create new request log entity
        $entity = new RequestLogEntity($this->request, $this->response);
        $entity->setUser($this->user);
        $entity->setMasterRequest($this->masterRequest);

        $this->resource->save($entity, true);
    }
}
