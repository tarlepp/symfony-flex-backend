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
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
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
     * @throws Throwable
     *
     * @testdox Test that `supportsClass` method returns `$expected` when using `$input` as input
     */
    public function testThatSupportClassReturnsExpected(bool $expected, mixed $input): void
    {
        [$apiKeyRepositoryMock, $rolesServiceMock] = $this->getMocks();

        $provider = new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock);

        static::assertSame($expected, $provider->supportsClass((string)$input));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method throws an exception
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $user = new User('username', 'password');

        [$apiKeyRepositoryMock, $rolesServiceMock] = $this->getMocks();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('API key cannot refresh user');

        (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->refreshUser($user);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method throws an exception when API key is not found
     */
    public function testThatLoadUserByUsernameThrowsAnException(): void
    {
        [$apiKeyRepositoryMock, $rolesServiceMock] = $this->getMocks();

        $apiKeyRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn(null);

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->loadUserByUsername('guid');
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method returns expected `ApiKeyUser` instance
     */
    public function testThatLoadUserByUsernameCreatesExpectedApiKeyUser(): void
    {
        $apiKey = new ApiKey();

        [$apiKeyRepositoryMock, $rolesServiceMock] = $this->getMocks();

        $apiKeyRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn($apiKey);

        $user = (new ApiKeyUserProvider($apiKeyRepositoryMock, $rolesServiceMock))
            ->loadUserByUsername('guid');

        static::assertSame($apiKey, $user->getApiKey());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getApiKeyForToken` method calls expected repository methods
     */
    public function testThatGetApiKeyForTokenCallsExpectedRepositoryMethod(): void
    {
        [$apiKeyRepositoryMock, $rolesServiceMock] = $this->getMocks();

        $apiKeyRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'some_token'])
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

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&ApiKeyRepository,
     *      1: \PHPUnit\Framework\MockObject\MockObject&RolesService,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(ApiKeyRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock(),
        ];
    }
}
