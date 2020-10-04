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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface RequestLoggerInterface
{
    /**
     * ResponseLogger constructor.
     *
     * @param array<int, string> $sensitiveProperties
     */
    public function __construct(LogRequestResource $resource, LoggerInterface $logger, array $sensitiveProperties);

    /**
     * Setter for response object.
     */
    public function setResponse(Response $response): self;

    /**
     * Setter for request object.
     */
    public function setRequest(Request $request): self;

    /**
     * Setter method for current user.
     */
    public function setUser(?User $user = null): self;

    /**
     * Setter method for current api key
     */
    public function setApiKey(?ApiKey $apiKey = null): self;

    /**
     * Setter method for 'master request' info.
     */
    public function setMasterRequest(bool $masterRequest): self;

    /**
     * Method to handle current response and log it to database.
     */
    public function handle(): void;
}
