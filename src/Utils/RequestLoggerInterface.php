<?php
declare(strict_types = 1);
/**
 * /src/Utils/RequestLoggerInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Entity\ApiKey;
use App\Resource\LogRequestResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

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
     */
    public function __construct(LogRequestResource $resource);

    /**
     * Setter for response object.
     *
     * @param Response $response
     *
     * @return RequestLoggerInterface
     */
    public function setResponse(Response $response): RequestLoggerInterface;

    /**
     * Setter for request object.
     *
     * @param Request $request
     *
     * @return RequestLoggerInterface
     */
    public function setRequest(Request $request): RequestLoggerInterface;

    /**
     * Setter method for current user.
     *
     * @param UserInterface|null $user
     *
     * @return RequestLoggerInterface
     */
    public function setUser(UserInterface $user = null): RequestLoggerInterface;

    /**
     * Setter method for current api key
     *
     * @param ApiKey|null $apiKey
     *
     * @return RequestLoggerInterface
     */
    public function setApiKey(ApiKey $apiKey = null): RequestLoggerInterface;

    /**
     * Setter method for 'master request' info.
     *
     * @param bool $masterRequest
     *
     * @return RequestLoggerInterface
     */
    public function setMasterRequest(bool $masterRequest): RequestLoggerInterface;

    /**
     * Method to handle current response and log it to database.
     *
     * @throws \Exception
     */
    public function handle(): void;
}
