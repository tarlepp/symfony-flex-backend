<?php
declare(strict_types = 1);
/**
 * /src/Utils/Interfaces/RequestLoggerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Interfaces;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Resource\LogRequestResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface RequestLoggerInterface
 *
 * @package App\Services\Interfaces
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface RequestLoggerInterface
{
    /**
     * ResponseLogger constructor.
     *
     * @param LogRequestResource $resource
     * @param LoggerInterface    $logger
     */
    public function __construct(LogRequestResource $resource, LoggerInterface $logger);

    /**
     * Setter for response object.
     *
     * @param Response $response
     *
     * @return RequestLoggerInterface
     */
    public function setResponse(Response $response): self;

    /**
     * Setter for request object.
     *
     * @param Request $request
     *
     * @return RequestLoggerInterface
     */
    public function setRequest(Request $request): self;

    /**
     * Setter method for current user.
     *
     * @param User|null $user
     *
     * @return RequestLoggerInterface
     */
    public function setUser(?User $user = null): self;

    /**
     * Setter method for current api key
     *
     * @param ApiKey|null $apiKey
     *
     * @return RequestLoggerInterface
     */
    public function setApiKey(?ApiKey $apiKey = null): self;

    /**
     * Setter method for 'master request' info.
     *
     * @param bool $masterRequest
     *
     * @return RequestLoggerInterface
     */
    public function setMasterRequest(bool $masterRequest): self;

    /**
     * Method to handle current response and log it to database.
     */
    public function handle(): void;
}
