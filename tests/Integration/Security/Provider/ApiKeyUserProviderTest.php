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
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;
use function assert;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Integration\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    private MockObject | ApiKeyRepository | null$apiKeyRepository = null;
    private MockObject | RolesService | null $rolesService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

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
        $provider = new ApiKeyUserProvider($this->getApiKeyRepository(), $this->getRolesService());

        static::assertSame($expected, $provider->supportsClass((string)$input));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method throws an exception
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('API key cannot refresh user');

        $user = new InMemoryUser('username', 'password');

        (new ApiKeyUserProvider($this->getApiKeyRepository(), $this->getRolesService()))
            ->refreshUser($user);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method throws an exception when API key is not found
     */
    public function testThatLoadUserByUsernameThrowsAnException(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        $this->getApiKeyRepositoryMock()
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn(null);

        (new ApiKeyUserProvider($this->getApiKeyRepository(), $this->getRolesService()))
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

        $this->getApiKeyRepositoryMock()
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn($apiKey);

        $user = (new ApiKeyUserProvider($this->getApiKeyRepository(), $this->getRolesService()))
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
        $this->getApiKeyRepositoryMock()
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'some_token'])
            ->willReturn(null);

        (new ApiKeyUserProvider($this->getApiKeyRepository(), $this->getRolesService()))
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

    private function getApiKeyRepository(): ApiKeyRepository
    {
        assert($this->apiKeyRepository instanceof ApiKeyRepository);

        return $this->apiKeyRepository;
    }

    private function getApiKeyRepositoryMock(): MockObject
    {
        assert($this->apiKeyRepository instanceof MockObject);

        return $this->apiKeyRepository;
    }

    private function getRolesService(): RolesService
    {
        assert($this->rolesService instanceof RolesService);

        return $this->rolesService;
    }
}
