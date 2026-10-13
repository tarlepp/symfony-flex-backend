<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Utils/RequestLoggerTest.php
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

final class RequestLoggerTest extends KernelTestCase
{
    #[TestDox('Test that log is not created if `Request` and `Response` object are not set')]
    public function testThatLogIsNotCreatedIfRequestAndResponseObjectsAreNotSet(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceStub = self::createStub(UserResource::class);
        $apiKeyResourceStub = self::createStub(ApiKeyResource::class);
        $loggerStub = self::createStub(LoggerInterface::class);

        $logRequestResourceMock
            ->expects($this->never())
            ->method('save');

        new RequestLogger($logRequestResourceMock, $userResourceStub, $apiKeyResourceStub, $loggerStub, [])
            ->handle();
    }

    #[TestDox('Test that log is not created if `Request` object is not set')]
    public function testThatLogIsNotCreatedIfRequestObjectIsNotSet(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceStub = self::createStub(UserResource::class);
        $apiKeyResourceStub = self::createStub(ApiKeyResource::class);
        $loggerStub = self::createStub(LoggerInterface::class);

        $logRequestResourceMock
            ->expects($this->never())
            ->method('save');

        new RequestLogger($logRequestResourceMock, $userResourceStub, $apiKeyResourceStub, $loggerStub, [])
            ->setResponse(new Response())
            ->handle();
    }

    #[TestDox('Test that log is not created if `Response` object is not set')]
    public function testThatLogIsNotCreatedIfResponseObjectIsNotSet(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceStub = self::createStub(UserResource::class);
        $apiKeyResourceStub = self::createStub(ApiKeyResource::class);
        $loggerStub = self::createStub(LoggerInterface::class);

        $logRequestResourceMock
            ->expects($this->never())
            ->method('save');

        new RequestLogger($logRequestResourceMock, $userResourceStub, $apiKeyResourceStub, $loggerStub, [])
            ->setRequest(new Request())
            ->handle();
    }

    #[TestDox('Test that log is created when `Request` and `Response` object are set')]
    public function testThatResourceSaveMethodIsCalled(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceStub = self::createStub(UserResource::class);
        $apiKeyResourceStub = self::createStub(ApiKeyResource::class);
        $loggerStub = self::createStub(LoggerInterface::class);

        $logRequestResourceMock
            ->expects($this->once())
            ->method('save')
            ->with();

        new RequestLogger($logRequestResourceMock, $userResourceStub, $apiKeyResourceStub, $loggerStub, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    #[TestDox('Test that `LoggerInterface::error` method is called when exception is thrown')]
    public function testThatLoggerIsCalledIfExceptionIsThrown(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceStub = self::createStub(UserResource::class);
        $apiKeyResourceStub = self::createStub(ApiKeyResource::class);
        $loggerMock = $this->getLoggerMock();

        $logRequestResourceMock
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception('test exception'));

        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('test exception');

        new RequestLogger($logRequestResourceMock, $userResourceStub, $apiKeyResourceStub, $loggerMock, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->handle();
    }

    #[TestDox('Test that `UserResource::getReference` method is called when `userId` is set')]
    public function testThatUserResourceMethodIsCalledWhenUserIdIsSet(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceMock = $this->getUserResourceMock();
        $apiKeyResourceMock = $this->getApiKeyResourceMock();
        $loggerStub = self::createStub(LoggerInterface::class);
        $user = new User();

        $userResourceMock
            ->expects($this->once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $apiKeyResourceMock
            ->expects(self::never())
            ->method('getReference');

        $logRequestResourceMock
            ->expects($this->once())
            ->method('save')
            ->with();

        new RequestLogger($logRequestResourceMock, $userResourceMock, $apiKeyResourceMock, $loggerStub, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setUserId($user->getId())
            ->handle();
    }

    #[TestDox('Test that `ApiKeyResource::getReference` method is called when `userId` is set')]
    public function testThatApiKeyResourceMethodIsCalledWhenUserIdIsSet(): void
    {
        $logRequestResourceMock = $this->getLogRequestResourceMock();
        $userResourceMock = $this->getUserResourceMock();
        $apiKeyResourceMock = $this->getApiKeyResourceMock();
        $loggerStub = self::createStub(LoggerInterface::class);
        $user = new ApiKey();

        $apiKeyResourceMock
            ->expects($this->once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        $userResourceMock
            ->expects(self::never())
            ->method('getReference');

        $logRequestResourceMock
            ->expects($this->once())
            ->method('save')
            ->with();

        new RequestLogger($logRequestResourceMock, $userResourceMock, $apiKeyResourceMock, $loggerStub, [])
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setApiKeyId($user->getId())
            ->handle();
    }

    /**
     * @psalm-return MockObject&LoggerInterface
     */
    private function getLoggerMock(): LoggerInterface
    {
        return $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    /**
     * @psalm-return MockObject&LogRequestResource
     */
    private function getLogRequestResourceMock(): MockObject
    {
        return $this->getMockBuilder(LogRequestResource::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @psalm-return MockObject&UserResource
     */
    private function getUserResourceMock(): MockObject
    {
        return $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @psalm-return MockObject&ApiKeyResource
     */
    private function getApiKeyResourceMock(): MockObject
    {
        return $this->getMockBuilder(ApiKeyResource::class)->disableOriginalConstructor()->getMock();
    }
}
