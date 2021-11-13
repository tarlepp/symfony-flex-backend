<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Provider/ApiKeyUserProviderTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Provider;

use App\Entity\ApiKey;
use App\Entity\User as UserEntity;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\Provider\ApiKeyUserProvider;
use App\Security\RolesService;
use Generator;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Integration\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatSupportClassReturnsExpected
     *
     * @param mixed $input
     *
     * @throws Throwable
     *
     * @testdox Test that `supportsClass` method returns `$expected` when using `$input` as input
     */
    public function testThatSupportClassReturnsExpected(bool $expected, $input): void
    {
        $apiKeyRepositoryMock = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rolesServiceMock = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock);

        self::assertSame($expected, $provider->supportsClass((string)$input));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method throws an exception
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $apiKeyRepositoryMock = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rolesServiceMock = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('API key cannot refresh user');

        $user = new InMemoryUser('username', 'password');

        (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->refreshUser($user);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByIdentifier` method throws an exception when API key is not found
     */
    public function testThatLoadUserByIdentifierThrowsAnException(): void
    {
        $apiKeyRepositoryMock = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rolesServiceMock = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyRepositoryMock
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'token' => 'guid',
            ])
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->loadUserByIdentifier('guid');
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByIdentifier` method returns expected `ApiKeyUser` instance
     */
    public function testThatLoadUserByIdentifierCreatesExpectedApiKeyUser(): void
    {
        $apiKeyRepositoryMock = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rolesServiceMock = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKey = new ApiKey();

        $apiKeyRepositoryMock
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'token' => 'guid',
            ])
            ->willReturn($apiKey);

        $user = (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->loadUserByIdentifier('guid');

        self::assertSame($apiKey, $user->getApiKey());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getApiKeyForToken` method calls expected repository methods
     */
    public function testThatGetApiKeyForTokenCallsExpectedRepositoryMethod(): void
    {
        $apiKeyRepositoryMock = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rolesServiceMock = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyRepositoryMock
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'token' => 'some_token',
            ])
            ->willReturn(null);

        (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->getApiKeyForToken('some_token');
    }

    /**
     * @return Generator<array{0: boolean, 1: boolean|string|int}>
     */
    public function dataProviderTestThatSupportClassReturnsExpected(): Generator
    {
        yield [false, true];
        yield [false, 'foobar'];
        yield [false, 123];
        yield [false, stdClass::class];
        yield [false, UserInterface::class];
        yield [false, UserEntity::class];
        yield [true, ApiKeyUser::class];
    }
}
