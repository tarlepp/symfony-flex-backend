<?php
declare(strict_types = 1);
/**
 * /src/Utils/Interfaces/RequestLoggerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Utils\Interfaces;

use App\Resource\ApiKeyResource;
use App\Resource\LogRequestResource;
use App\Resource\UserResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface RequestLoggerInterface
 *
 * @package App\Services\Interfaces
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface RequestLoggerInterface
{
    /**
     * RequestLogger constructor.
     *
     * @codeCoverageIgnore This is needed because variables are multiline
     *
     * @param array<int, string> $sensitiveProperties
     */
    public function __construct(
        LogRequestResource $logRequestResource,
        UserResource $userResource,
        ApiKeyResource $apiKeyResource,
        LoggerInterface $logger,
        array $sensitiveProperties,
    );

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
    public function setUserId(string $userId): self;

    /**
     * Setter method for current api key
     */
    public function setApiKeyId(string $apiKeyId): self;

    /**
     * Setter method for 'main request' info.
     */
    public function setMainRequest(bool $mainRequest): self;

    /**
     * Method to handle current response and log it to database.
     */
    public function handle(): void;
}
