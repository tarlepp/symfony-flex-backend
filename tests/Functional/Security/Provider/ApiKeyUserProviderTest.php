<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Security/Provider/ApiKeyUserProviderTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Security\Provider;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\Provider\ApiKeyUserProvider;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;
use function array_map;
use function assert;
use function str_pad;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Functional\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    private ?ApiKeyUserProvider $apiKeyUserProvider = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        /**
         * @var ManagerRegistry $managerRegistry
         */
        $managerRegistry = static::getContainer()->get('doctrine');

        /**
         * @var RolesService $rolesService
         */
        $rolesService = static::getContainer()->get(RolesService::class);

        $repository = ApiKeyRepository::class;

        $this->apiKeyUserProvider = new ApiKeyUserProvider(new $repository($managerRegistry), $rolesService);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @testdox Test that `getApiKeyForToken` method returns expected when using `$shortRole` as token base.
     */
    public function testThatGetApiKeyReturnsExpected(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '_');

        $apiKey = $this->getApiKeyUserProvider()->getApiKeyForToken($token);

        static::assertInstanceOf(ApiKey::class, $apiKey);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @testdox Test that `getApiKeyForToken` method returns null when using `$shortRole` as an invalid token base.
     */
    public function testThatGetApiKeyReturnsNullForInvalidToken(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '-');

        $apiKey = $this->getApiKeyUserProvider()->getApiKeyForToken($token);

        static::assertNull($apiKey);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidGuid(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        $this->getApiKeyUserProvider()->loadUserByUsername((string)time());
    }

    /**
     * @dataProvider dataProviderTestThatLoadUserByUsernameWorksAsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $roles
     * @psalm-param StringableArrayObject $roles
     *
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` returns `ApiKeyUser` with `$roles` roles when using `$token` as an input.
     */
    public function testThatLoadUserByUsernameWorksAsExpected(string $token, StringableArrayObject $roles): void
    {
        $apiKeyUser = $this->getApiKeyUserProvider()->loadUserByUsername($token);

        static::assertInstanceOf(ApiKeyUser::class, $apiKeyUser);
        static::assertSame($roles->getArrayCopy(), $apiKeyUser->getApiKey()->getRoles());
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('API key cannot refresh user');

        $user = new InMemoryUser('username', 'password');

        $this->getApiKeyUserProvider()->refreshUser($user);
    }

    /**
     * @dataProvider dataProviderTestThatSupportsClassReturnsExpected
     *
     * @testdox Test that `supportsClass` returns `$expected` when using `$class` as an input.
     */
    public function testThatSupportsClassReturnsExpected(bool $expected, string $class): void
    {
        static::assertSame($expected, $this->getApiKeyUserProvider()->supportsClass($class));
    }

    /**
     * @return array<int, array{0: string}>
     */
    public function dataProviderTestThatGetApiKeyReturnsExpected(): array
    {
        /** @var RolesService $rolesService */
        $rolesService = static::getContainer()->get(RolesService::class);

        $iterator = static fn (string $role): array => [$rolesService->getShort($role)];

        return array_map($iterator, $rolesService->getRoles());
    }

    /**
     * @return array<int, array{0: string, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatLoadUserByUsernameWorksAsExpected(): array
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = static::getContainer()->get('doctrine');
        $repositoryClass = ApiKeyRepository::class;
        $repository = new $repositoryClass($managerRegistry);

        $iterator = static function (ApiKey $apiKey): array {
            return [
                $apiKey->getToken(),
                new StringableArrayObject($apiKey->getRoles()),
            ];
        };

        return array_map($iterator, $repository->findAll());
    }

    /**
     * @return Generator<array{0: boolean, 1: class-string<\Symfony\Component\Security\Core\User\UserInterface>}>
     */
    public function dataProviderTestThatSupportsClassReturnsExpected(): Generator
    {
        yield [false, InMemoryUser::class];
        yield [true, ApiKeyUser::class];
    }

    private function getApiKeyUserProvider(): ApiKeyUserProvider
    {
        assert($this->apiKeyUserProvider instanceof ApiKeyUserProvider);

        return $this->apiKeyUserProvider;
    }
}
