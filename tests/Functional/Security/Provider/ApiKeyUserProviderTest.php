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
use App\Tests\Utils\StringableArrayObject;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;
use function array_map;
use function str_pad;

/**
 * @package App\Tests\Functional\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class ApiKeyUserProviderTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetApiKeyReturnsExpected')]
    #[TestDox('Test that `getApiKeyForToken` method returns expected when using `$shortRole` as token base.')]
    public function testThatGetApiKeyReturnsExpected(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '_');

        $apiKey = $this->getApiKeyUserProvider()->getApiKeyForToken($token);

        self::assertInstanceOf(ApiKey::class, $apiKey);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetApiKeyReturnsExpected')]
    #[TestDox('Test that `getApiKeyForToken` method returns null when using `$shortRole` as an invalid token base.')]
    public function testThatGetApiKeyReturnsNullForInvalidToken(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '-');

        $apiKey = $this->getApiKeyUserProvider()->getApiKeyForToken($token);

        self::assertNull($apiKey);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadUserByIdentifierThrowsAnExceptionWithInvalidGuid(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        $this->getApiKeyUserProvider()->loadUserByIdentifier((string)time());
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $roles
     * @psalm-param StringableArrayObject $roles
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatLoadUserByIdentifierWorksAsExpected')]
    #[TestDox('Test that `loadUserByIdentifier` returns `ApiKeyUser` with `$roles` roles when using `$token` input')]
    public function testThatLoadUserByIdentifierWorksAsExpected(string $token, StringableArrayObject $roles): void
    {
        $apiKeyUser = $this->getApiKeyUserProvider()->loadUserByIdentifier($token);

        self::assertSame($roles->getArrayCopy(), $apiKeyUser->getRoles());
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
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSupportsClassReturnsExpected')]
    #[TestDox('Test that `supportsClass` returns `$expected` when using `$class` as an input.')]
    public function testThatSupportsClassReturnsExpected(bool $expected, string $class): void
    {
        self::assertSame($expected, $this->getApiKeyUserProvider()->supportsClass($class));
    }

    /**
     * @throws Throwable
     *
     * @return array<int, array{0: string}>
     */
    public static function dataProviderTestThatGetApiKeyReturnsExpected(): array
    {
        [, $rolesService] = self::getServices();

        $iterator = static fn (string $role): array => [$rolesService->getShort($role)];

        return array_map($iterator, $rolesService->getRoles());
    }

    /**
     * @throws Throwable
     *
     * @psalm-return array<int, array{0: string, 1: StringableArrayObject}>
     * @phpstan-return array<int, array{0: string, 1: StringableArrayObject<array<int, string>>}>
     */
    public static function dataProviderTestThatLoadUserByIdentifierWorksAsExpected(): array
    {
        [$managerRegistry, $rolesService] = self::getServices();

        $repository = new ApiKeyRepository($managerRegistry);

        $iterator = static fn (ApiKey $apiKey): array => [
            $apiKey->getToken(),
            new StringableArrayObject([...$rolesService->getInheritedRoles($apiKey->getRoles())]),
        ];

        return array_map($iterator, $repository->findAll());
    }

    /**
     * @return Generator<array{0: boolean, 1: class-string<\Symfony\Component\Security\Core\User\UserInterface>}>
     */
    public static function dataProviderTestThatSupportsClassReturnsExpected(): Generator
    {
        yield [false, InMemoryUser::class];
        yield [true, ApiKeyUser::class];
    }

    /**
     * @throws Throwable
     */
    private function getApiKeyUserProvider(): ApiKeyUserProvider
    {
        [$managerRegistry, $rolesService] = self::getServices();
        $repository = ApiKeyRepository::class;

        return new ApiKeyUserProvider(new $repository($managerRegistry), $rolesService);
    }

    /**
     * @throws Throwable
     *
     * @return array{0: ManagerRegistry, 1: RolesService}
     */
    private static function getServices(): array
    {
        self::bootKernel();

        /** @psalm-var ManagerRegistry $managerRegistry */
        $managerRegistry = static::getContainer()->get('doctrine');

        /** @psalm-var RolesService $rolesService */
        $rolesService = static::getContainer()->get(RolesService::class);

        return [$managerRegistry, $rolesService];
    }
}
