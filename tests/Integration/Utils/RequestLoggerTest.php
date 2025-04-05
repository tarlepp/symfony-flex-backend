<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/RequestLoggerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Utils;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Resource\ApiKeyResource;
use App\Resource\LogRequestResource;
use App\Resource\UserResource;
use App\Utils\RequestLogger;
use Exception;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class RequestLoggerTest extends KernelTestCase
{
    #[TestDox('Test that log is not created if `Request` and `Response` object are not set')]
    public function testThatLogIsNotCreatedIfRequestAndResponseObjectsAreNotSet(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();

        $logRequestResource
            ->expects(self::never())
            ->method('save');

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->handle();
    }

    #[TestDox('Test that log is not created if `Request` object is not set')]
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();

        $logRequestResource
            ->expects(self::never())
            ->method('save');

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->setResponse(new Response())
            ->handle();
    }

    #[TestDox('Test that log is not created if `Response` object is not set')]
    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();

        $logRequestResource
            ->expects(self::never())
            ->method('save');

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->setRequest(new Request())
            ->handle();
    }

    #[TestDox('Test that log is created when `Request` and `Response` object are set')]
    public function testThatResourceSaveMethodIsCalled(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();

        $logRequestResource
            ->expects($this->once())
            ->method('save')
            ->with();

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    #[TestDox('Test that `LoggerInterface::error` method is called when exception is thrown')]
    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();

        $logRequestResource
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception('test exception'));

        $logger
            ->expects($this->once())
            ->method('error')
            ->with('test exception');

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    #[TestDox('Test that `UserResource::getReference` method is called when `userId` is set')]
    public function testThatUserResourceMethodIsCalledWhenUserIdIsSet(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();
        $user = new User();

        $userResource
            ->expects($this->once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $apiKeyResource
            ->expects(self::never())
            ->method('getReference');

        $logRequestResource
            ->expects($this->once())
            ->method('save')
            ->with();

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setUserId($user->getId())
            ->handle();
    }

    #[TestDox('Test that `ApiKeyResource::getReference` method is called when `userId` is set')]
    public function testThatApiKeyResourceMethodIsCalledWhenUserIdIsSet(): void
    {
        $logRequestResource = $this->getLogRequestResource();
        $userResource = $this->getUserResource();
        $apiKeyResource = $this->getApiKeyResource();
        $logger = $this->getLogger();
        $user = new ApiKey();

        $apiKeyResource
            ->expects($this->once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $userResource
            ->expects(self::never())
            ->method('getReference');

        $logRequestResource
            ->expects($this->once())
            ->method('save')
            ->with();

        new RequestLogger($logRequestResource, $userResource, $apiKeyResource, $logger, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setApiKeyId($user->getId())
            ->handle();
    }

    /**
     * @psalm-return MockObject&LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    /**
     * @psalm-return MockObject&LogRequestResource
     */
    private function getLogRequestResource(): MockObject
    {
        return $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @psalm-return MockObject&UserResource
     */
    private function getUserResource(): MockObject
    {
        return $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @psalm-return MockObject&ApiKeyResource
     */
    private function getApiKeyResource(): MockObject
    {
        return $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();
    }
}
